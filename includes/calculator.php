<?php
/**
 * セキュリティスコア・想定被害額・商材プラン算出ロジック
 * すべての係数・定義は config.php から参照する
 */

require_once __DIR__ . '/config.php';

/**
 * セキュリティスコアを算出する
 * 回答: yes=100% / unknown=50% / no=0%
 */
function calculate_security_score(array $answers): array
{
    $category_scores = [];
    $total_score = 0;

    foreach (ASSESSMENT_CATEGORIES as $cat_id => $cat) {
        $questions  = $cat['questions'];
        $q_count    = count($questions);
        $obtained   = 0.0;

        foreach (array_keys($questions) as $q_key) {
            // 新形式（answers[Q01]）と旧形式どちらにも対応
            $answer = $answers['answers'][$q_key]
                ?? $answers[$q_key]
                ?? 'no';
            if ($answer === 'yes')     $obtained += 1.0;
            elseif ($answer === 'unknown') $obtained += 0.5;
        }

        $rate      = $q_count > 0 ? $obtained / $q_count : 0;
        $cat_score = round($rate * $cat['points']);

        $category_scores[$cat_id] = [
            'score' => $cat_score,
            'max'   => $cat['points'],
            'label' => $cat['label'],
            'icon'  => $cat['icon'],
            'rate'  => round($rate * 100),
        ];
        $total_score += $cat_score;
    }

    // 調整係数
    $has_personal = !empty($answers['has_personal_info']) && $answers['has_personal_info'] !== '0';
    $employees    = intval($answers['employees'] ?? 0);
    if ($has_personal)    $total_score = max(0, $total_score - 5);
    if ($employees >= 500) $total_score = max(0, $total_score - 3);
    $total_score = min(100, $total_score);

    // リスクレベル判定
    $risk_level   = 'critical';
    $risk_label   = '危険';
    $risk_color   = '#c62828';
    $risk_message = '早急な対策が必要です';
    foreach (RISK_LEVELS as $r) {
        if ($total_score >= $r['min'] && $total_score <= $r['max']) {
            $risk_level   = $r['level'];
            $risk_label   = $r['label'];
            $risk_color   = $r['color'];
            $risk_message = $r['message'];
            break;
        }
    }

    return [
        'score'           => $total_score,
        'category_scores' => $category_scores,
        'risk_level'      => $risk_level,
        'risk_label'      => $risk_label,
        'risk_color'      => $risk_color,
        'risk_message'    => $risk_message,
    ];
}

/**
 * 想定被害額を算出する（最小・中央・最大レンジ）
 */
function calculate_damage(array $post): array
{
    $industry     = $post['industry'] ?? 'other';
    $emp_size     = $post['employee_count'] ?? 'small';
    $score        = intval($post['security_score'] ?? 50);
    $has_personal = !empty($post['has_personal_info']) && $post['has_personal_info'] !== '0';

    $emp_data            = EMPLOYEE_DAMAGE_BASE[$emp_size] ?? EMPLOYEE_DAMAGE_BASE['small'];
    $base                = $emp_data['base'];
    $industry_multiplier = INDUSTRY_MULTIPLIERS[$industry] ?? 1.0;

    $score_multiplier = 1.5;
    foreach (SCORE_DAMAGE_MULTIPLIERS as $sm) {
        if ($score >= $sm['min'] && $score <= $sm['max']) {
            $score_multiplier = $sm['multiplier'];
            break;
        }
    }

    $center = round($base * $industry_multiplier * $score_multiplier);
    $min    = round($center * 0.6);
    $max    = round($center * 1.6);
    if ($has_personal) $max += 1000;

    $breakdown = [
        ['label' => 'ランサムウェア（業務停止・復旧費用）',   'amount' => round($center * 0.35), 'risk' => _srisk($score, 40)],
        ['label' => '情報漏洩（調査・通知・賠償費用）',       'amount' => round($center * 0.25) + ($has_personal ? 500 : 0), 'risk' => _srisk($score, 45)],
        ['label' => 'フィッシング・不正送金被害',             'amount' => round($center * 0.20), 'risk' => _srisk($score, 35)],
        ['label' => '業務停止・機会損失',                    'amount' => round($center * 0.20), 'risk' => _srisk($score, 40)],
    ];

    return [
        'min'       => $min,
        'center'    => $center,
        'max'       => $max,
        'total'     => $max,   // 後方互換
        'breakdown' => $breakdown,
    ];
}

function _srisk(int $score, int $threshold): string
{
    if ($score >= $threshold + 30) return 'low';
    if ($score >= $threshold)      return 'medium';
    return 'high';
}

/**
 * PC台数から最適なDDHBOXプランを選定する
 */
function recommend_ddhbox_plan(int $pc_count): array
{
    foreach (DDHBOX_PLANS as $plan) {
        if ($pc_count <= $plan['max_devices']) {
            return $plan;
        }
    }
    $plans = DDHBOX_PLANS;
    return end($plans);
}

/**
 * necfru MAM/DAMの費用試算を行う
 */
function estimate_necfru_cost(int $employees): array
{
    $storage_gb   = $employees * NECFRU_PRICE['storage_per_person'];
    $hot_gb       = round($storage_gb * 0.3);
    $cold_gb      = $storage_gb - $hot_gb;
    $storage_cost = round($hot_gb * NECFRU_PRICE['hot_per_gb'] + $cold_gb * NECFRU_PRICE['cold_per_gb']);
    $monthly      = NECFRU_PRICE['monthly_base'] + $storage_cost;

    return [
        'monthly_base'  => NECFRU_PRICE['monthly_base'],
        'storage_gb'    => $storage_gb,
        'storage_cost'  => $storage_cost,
        'monthly_total' => $monthly,
        'annual_total'  => $monthly * 12,
    ];
}

/**
 * 改善推奨事項を生成する
 */
function get_recommendations(array $answers): array
{
    $recs = [];
    $cats = [];

    foreach (ASSESSMENT_CATEGORIES as $cat_id => $cat) {
        $q_count  = count($cat['questions']);
        $obtained = 0.0;
        foreach (array_keys($cat['questions']) as $q_key) {
            $answer = $answers['answers'][$q_key] ?? $answers[$q_key] ?? 'no';
            if ($answer === 'yes')     $obtained += 1.0;
            elseif ($answer === 'unknown') $obtained += 0.5;
        }
        $cats[$cat_id] = $q_count > 0 ? $obtained / $q_count : 0;
    }

    if (($cats['CAT-01'] ?? 1) < 0.6) {
        $recs[] = ['priority' => 'high', 'text' => 'UTM/次世代ファイアウォールの導入・更新に加え、C2サーバへの通信遮断（出口対策）が急務です。DDHBOXの導入でネットワークの不正通信を全自動で遮断できます。'];
    }
    if (($cats['CAT-04'] ?? 1) < 0.6) {
        $recs[] = ['priority' => 'high', 'text' => '多要素認証（MFA）の導入で、パスワード漏洩時の不正ログインリスクを大幅に低減できます。'];
    }
    if (($cats['CAT-02'] ?? 1) < 0.6) {
        $recs[] = ['priority' => 'high', 'text' => 'EDR（エンドポイント検知・対応）ソリューションにより、マルウェア感染を早期検知・封じ込めできます。'];
    }
    if (($cats['CAT-03'] ?? 1) < 0.6) {
        $recs[] = ['priority' => 'medium', 'text' => 'オフサイト日次バックアップの整備が急務です。necfru MAM/DAMの導入で安全なクラウド保管・BCP対策が実現できます。'];
    }
    if (($cats['CAT-05'] ?? 1) < 0.6) {
        $recs[] = ['priority' => 'medium', 'text' => 'セキュリティ教育・フィッシング訓練の定期実施で、人的ミスによるインシデントを削減できます。'];
    }

    return $recs;
}
