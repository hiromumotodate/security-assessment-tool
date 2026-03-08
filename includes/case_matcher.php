<?php
/**
 * 診断結果と実際の被害事例をマッチングするロジック
 * 業種・規模・弱点カテゴリのスコアリングで関連度を算出し、上位事例を返す
 */

require_once __DIR__ . '/cases.php';

/**
 * 診断情報をもとに関連事例を取得する
 *
 * @param string $industry       業種コード
 * @param string $employee_count 従業員数区分（small/medium/large/enterprise）
 * @param array  $weak_categories スコアが低いカテゴリIDの配列 例: ['CAT-01', 'CAT-03']
 * @param int    $max             取得件数上限（デフォルト3件）
 * @return array スコア順にソートされた事例の配列
 */
function get_related_cases(string $industry, string $employee_count, array $weak_categories, int $max = 3): array
{
    $scored = [];

    foreach (SECURITY_CASES as $case) {
        $score = 0;

        // 業種一致：最重要（+40点）
        if (in_array($industry, $case['industry'], true)) {
            $score += 40;
        }
        // 業種が近い汎用事例（otherを含む場合は+10点）
        if (in_array('other', $case['industry'], true) && $score === 0) {
            $score += 10;
        }

        // 規模一致（+20点）
        if (in_array($employee_count, $case['size'], true)) {
            $score += 20;
        }

        // 弱点カテゴリの重複（1カテゴリにつき+15点、上限45点）
        $overlap = array_intersect($weak_categories, $case['weak_points']);
        $score += min(count($overlap) * 15, 45);

        // スコア0（まったく無関係）はスキップ
        if ($score === 0) {
            continue;
        }

        $scored[] = array_merge($case, ['_relevance_score' => $score]);
    }

    // 関連度スコア降順 → 被害額降順でソート
    usort($scored, function ($a, $b) {
        if ($b['_relevance_score'] !== $a['_relevance_score']) {
            return $b['_relevance_score'] - $a['_relevance_score'];
        }
        return $b['damage_amount'] - $a['damage_amount'];
    });

    return array_slice($scored, 0, $max);
}

/**
 * 診断スコアをもとに弱点カテゴリを特定する
 *
 * @param array $answers 設問回答（assessment.phpのPOST値）
 * @return array 弱点と判定されたカテゴリIDの配列
 */
function get_weak_categories(array $answers): array
{
    // カテゴリごとの設問マッピング
    $category_questions = [
        'CAT-01' => ['firewall', 'vpn', 'wifi_separation', 'physical_access'],
        'CAT-02' => ['antivirus', 'patch_management', 'device_protection'],
        'CAT-03' => ['backup', 'incident_response'],
        'CAT-04' => ['mfa', 'account_management'],
        'CAT-05' => ['security_training'],
    ];

    $weak = [];

    foreach ($category_questions as $cat_id => $questions) {
        $total    = count($questions) * 2;
        $obtained = 0;

        foreach ($questions as $q) {
            $obtained += intval($answers[$q] ?? 0);
        }

        $rate = $total > 0 ? ($obtained / $total) : 0;

        // 60%未満を「弱点」と判定
        if ($rate < 0.6) {
            $weak[] = $cat_id;
        }
    }

    return $weak;
}

/**
 * 診断スコアと想定被害額を事例の実被害額と比較するコメントを生成する
 *
 * @param int   $estimated_damage 想定被害額（万円）
 * @param array $case             事例データ
 * @return string 比較コメント
 */
function get_comparison_comment(int $estimated_damage, array $case): string
{
    $ratio = $case['damage_amount'] > 0
        ? round($estimated_damage / $case['damage_amount'] * 100)
        : 0;

    if ($ratio >= 80) {
        return '貴社の想定被害額はこの事例と同規模です。同様の被害が起こりうる状況です。';
    } elseif ($ratio >= 40) {
        return 'この事例の約' . round($ratio / 10) * 10 . '%相当の被害が想定されます。対策は急務です。';
    } else {
        return '規模は異なりますが、同様の攻撃手法が貴社にも適用されるリスクがあります。';
    }
}
