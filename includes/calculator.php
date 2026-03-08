<?php

/**
 * セキュリティスコアと想定被害額を算出するロジック
 * 参考: IPA「情報セキュリティ10大脅威」、総務省統計、Ponemon Institute調査
 */

function calculate_security_score(array $answers): array
{
    $check_items = [
        'physical_access',
        'device_protection',
        'firewall',
        'vpn',
        'wifi_separation',
        'antivirus',
        'patch_management',
        'mfa',
        'account_management',
        'backup',
        'incident_response',
        'security_training',
    ];

    $total_score = 0;
    $max_score   = count($check_items) * 2;

    foreach ($check_items as $item) {
        $total_score += intval($answers[$item] ?? 0);
    }

    $score_percent = round(($total_score / $max_score) * 100);

    if ($score_percent >= 80) {
        $risk_level = 'low';
        $risk_label = '低リスク';
    } elseif ($score_percent >= 50) {
        $risk_level = 'medium';
        $risk_label = '中リスク';
    } elseif ($score_percent >= 30) {
        $risk_level = 'high';
        $risk_label = '高リスク';
    } else {
        $risk_level = 'critical';
        $risk_label = '危険';
    }

    return [
        'score'         => $score_percent,
        'total_score'   => $total_score,
        'max_score'     => $max_score,
        'risk_level'    => $risk_level,
        'risk_label'    => $risk_label,
    ];
}


function calculate_damage(array $post): array
{
    $industry          = $post['industry'] ?? 'other';
    $employee_count    = $post['employee_count'] ?? 'small';
    $annual_revenue    = intval($post['annual_revenue'] ?? 0);
    $personal_data_count = $post['personal_data_count'] ?? 'none';
    $score             = intval($post['security_score'] ?? 50);

    // 業種別リスク係数（金融・医療は高め）
    $industry_multiplier = [
        'finance'      => 2.5,
        'medical'      => 2.2,
        'retail'       => 1.8,
        'manufacturing'=> 1.5,
        'it'           => 1.6,
        'government'   => 2.0,
        'education'    => 1.3,
        'other'        => 1.2,
    ];

    // 従業員規模別ベース被害額（万円）
    $base_damage = [
        'small'      => 1500,
        'medium'     => 5000,
        'large'      => 15000,
        'enterprise' => 50000,
    ];

    // 個人情報件数による追加被害（1件あたり約500〜1000円の賠償リスク）
    $personal_data_damage = [
        'none'   => 0,
        'small'  => 250,     // 500件×500円
        'medium' => 3750,    // 7,500件×500円
        'large'  => 37500,   // 75,000件×500円
        'xlarge' => 250000,  // 500,000件×500円
    ];

    $base       = $base_damage[$employee_count] ?? 1500;
    $multiplier = $industry_multiplier[$industry] ?? 1.2;
    $pdata      = $personal_data_damage[$personal_data_count] ?? 0;

    // リスクスコアが低いほど被害額を増加（0点=2倍、100点=0.5倍）
    $risk_factor = 2.0 - ($score / 100) * 1.5;

    $damage_base     = round($base * $multiplier * $risk_factor);
    $damage_total    = $damage_base + $pdata;

    // 売上高がある場合、売上の一定割合も加算
    if ($annual_revenue > 0) {
        $revenue_loss = round($annual_revenue * 10000 * 0.08); // 売上の8%損失想定（万円→円で計算）
        $revenue_loss_man = round($revenue_loss / 10000);       // 万円に戻す
        $damage_total += $revenue_loss_man;
    }

    // 被害シナリオ別内訳
    $breakdown = [
        [
            'label'  => 'ランサムウェア被害（業務停止・復旧費用）',
            'amount' => round($damage_base * 0.35),
            'risk'   => get_scenario_risk($score, 'ransomware'),
        ],
        [
            'label'  => '情報漏洩（調査・通知・賠償費用）',
            'amount' => round(($damage_base * 0.25) + $pdata),
            'risk'   => get_scenario_risk($score, 'breach'),
        ],
        [
            'label'  => 'フィッシング・不正送金被害',
            'amount' => round($damage_base * 0.20),
            'risk'   => get_scenario_risk($score, 'phishing'),
        ],
        [
            'label'  => '業務停止・機会損失',
            'amount' => round($damage_base * 0.20),
            'risk'   => get_scenario_risk($score, 'downtime'),
        ],
    ];

    return [
        'total'     => $damage_total,
        'base'      => $damage_base,
        'breakdown' => $breakdown,
    ];
}


function get_scenario_risk(int $score, string $scenario): string
{
    $thresholds = [
        'ransomware' => [70, 40],
        'breach'     => [75, 45],
        'phishing'   => [65, 35],
        'downtime'   => [70, 40],
    ];

    [$low, $high] = $thresholds[$scenario] ?? [70, 40];

    if ($score >= $low) return 'low';
    if ($score >= $high) return 'medium';
    return 'high';
}


function get_recommendations(array $answers): array
{
    $recs = [];

    if (intval($answers['firewall'] ?? 0) < 2) {
        $recs[] = ['priority' => 'high', 'text' => 'UTM/次世代ファイアウォールの導入・更新により、外部からの不正アクセスを遮断できます。'];
    }
    if (intval($answers['mfa'] ?? 0) < 2) {
        $recs[] = ['priority' => 'high', 'text' => '多要素認証（MFA）の導入で、パスワード漏洩時の不正ログインリスクを大幅に低減できます。'];
    }
    if (intval($answers['antivirus'] ?? 0) < 2) {
        $recs[] = ['priority' => 'high', 'text' => 'EDR（エンドポイント検知・対応）ソリューションにより、マルウェア感染を早期検知・封じ込めできます。'];
    }
    if (intval($answers['backup'] ?? 0) < 2) {
        $recs[] = ['priority' => 'medium', 'text' => 'オフサイト日次バックアップを整備することで、ランサムウェア被害時の業務継続が可能になります。'];
    }
    if (intval($answers['patch_management'] ?? 0) < 2) {
        $recs[] = ['priority' => 'medium', 'text' => 'パッチ管理ツールの導入で、既知の脆弱性を自動的かつ迅速に修正できます。'];
    }
    if (intval($answers['security_training'] ?? 0) < 2) {
        $recs[] = ['priority' => 'medium', 'text' => 'セキュリティ教育・フィッシング訓練の定期実施で、人的ミスによるインシデントを削減できます。'];
    }
    if (intval($answers['incident_response'] ?? 0) < 2) {
        $recs[] = ['priority' => 'low', 'text' => 'インシデント対応手順の策定・訓練により、被害発生時の復旧時間（RTO）を短縮できます。'];
    }
    if (intval($answers['physical_access'] ?? 0) < 2) {
        $recs[] = ['priority' => 'low', 'text' => 'ICカードや生体認証による入退室管理で、物理的な不正アクセスを防止できます。'];
    }

    return $recs;
}
