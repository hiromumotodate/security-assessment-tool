<?php
/**
 * 営業履歴管理ページ
 * Supabase に保存された診断結果を一覧表示する
 */
require_once __DIR__ . '/includes/supabase.php';
require_once __DIR__ . '/includes/config.php';

// 絞り込みパラメータ
$filter_industry = $_GET['industry'] ?? '';
$filter_risk     = $_GET['risk'] ?? '';
$sort            = $_GET['sort'] ?? 'created_at';
$order           = $_GET['order'] ?? 'desc';

// Supabase からデータ取得
$query_parts = [
    'order=' . urlencode($sort . '.' . $order),
    'limit=200',
];
if ($filter_industry) $query_parts[] = 'industry=eq.' . urlencode($filter_industry);
if ($filter_risk)     $query_parts[] = 'risk_level=eq.' . urlencode($filter_risk);

$rows = supabase_select('assessments', implode('&', $query_parts));
$total = count($rows);

// リスクバッジ色
function risk_badge(string $level): string {
    $colors = ['critical' => '#c62828', 'high' => '#e65100', 'medium' => '#f57f17', 'low' => '#2e7d32'];
    $labels = ['critical' => '危険', 'high' => '高リスク', 'medium' => '中リスク', 'low' => '低リスク'];
    $color = $colors[$level] ?? '#999';
    $label = $labels[$level] ?? $level;
    return "<span style='background:{$color};color:#fff;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:bold;'>{$label}</span>";
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>営業履歴管理 | セキュリティ診断ツール</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #f5f7fa; font-family: 'Helvetica Neue', sans-serif; }
        .admin-wrap { max-width: 1200px; margin: 0 auto; padding: 24px 16px; }
        .admin-header { background: #1a237e; color: #fff; padding: 20px 24px; border-radius: 8px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
        .admin-header h1 { margin: 0; font-size: 20px; }
        .admin-header a { color: #90caf9; font-size: 14px; }
        .filter-bar { background: #fff; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px; display: flex; gap: 16px; align-items: center; flex-wrap: wrap; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .filter-bar label { font-size: 13px; color: #555; display: flex; flex-direction: column; gap: 4px; }
        .filter-bar select, .filter-bar input { padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
        .filter-bar button { padding: 8px 16px; background: #1a237e; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .stats-bar { display: flex; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-card { background: #fff; border-radius: 8px; padding: 16px 20px; flex: 1; min-width: 140px; box-shadow: 0 1px 4px rgba(0,0,0,.08); text-align: center; }
        .stat-card .num { font-size: 28px; font-weight: bold; color: #1a237e; }
        .stat-card .lbl { font-size: 12px; color: #888; margin-top: 4px; }
        .table-wrap { background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #e8eaf6; color: #283593; padding: 10px 12px; text-align: left; font-weight: 600; white-space: nowrap; }
        td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
        tr:hover td { background: #f8f9ff; }
        .score-bar { height: 6px; border-radius: 3px; background: #e0e0e0; margin-top: 4px; }
        .score-fill { height: 100%; border-radius: 3px; }
        .damage-amount { font-weight: bold; color: #c62828; }
        .no-data { text-align: center; padding: 60px; color: #aaa; font-size: 16px; }
        .detail-link { color: #1a237e; text-decoration: none; font-size: 12px; }
    </style>
</head>
<body>
<div class="admin-wrap">
    <div class="admin-header">
        <h1>📊 営業履歴・診断結果管理</h1>
        <a href="index.php">← 新規診断へ</a>
    </div>

    <!-- 絞り込みフォーム -->
    <form method="GET" class="filter-bar">
        <label>業種
            <select name="industry">
                <option value="">すべて</option>
                <?php foreach (INDUSTRY_LABELS as $key => $label): ?>
                <option value="<?= htmlspecialchars($key) ?>" <?= $filter_industry === $key ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>リスクレベル
            <select name="risk">
                <option value="">すべて</option>
                <option value="critical" <?= $filter_risk === 'critical' ? 'selected' : '' ?>>危険</option>
                <option value="high" <?= $filter_risk === 'high' ? 'selected' : '' ?>>高リスク</option>
                <option value="medium" <?= $filter_risk === 'medium' ? 'selected' : '' ?>>中リスク</option>
                <option value="low" <?= $filter_risk === 'low' ? 'selected' : '' ?>>低リスク</option>
            </select>
        </label>
        <label>並び順
            <select name="sort">
                <option value="created_at" <?= $sort === 'created_at' ? 'selected' : '' ?>>診断日時</option>
                <option value="security_score" <?= $sort === 'security_score' ? 'selected' : '' ?>>スコア</option>
                <option value="damage_max" <?= $sort === 'damage_max' ? 'selected' : '' ?>>被害額</option>
            </select>
        </label>
        <label>順序
            <select name="order">
                <option value="desc" <?= $order === 'desc' ? 'selected' : '' ?>>降順</option>
                <option value="asc" <?= $order === 'asc' ? 'selected' : '' ?>>昇順</option>
            </select>
        </label>
        <button type="submit">絞り込む</button>
        <a href="admin.php" style="font-size:13px;color:#888;">リセット</a>
    </form>

    <!-- 集計カード -->
    <?php
    $critical_count = count(array_filter($rows, fn($r) => $r['risk_level'] === 'critical'));
    $high_count     = count(array_filter($rows, fn($r) => $r['risk_level'] === 'high'));
    $avg_score      = $total > 0 ? round(array_sum(array_column($rows, 'security_score')) / $total) : 0;
    $total_damage   = array_sum(array_column($rows, 'damage_max'));
    ?>
    <div class="stats-bar">
        <div class="stat-card"><div class="num"><?= $total ?></div><div class="lbl">総診断件数</div></div>
        <div class="stat-card"><div class="num" style="color:#c62828;"><?= $critical_count ?></div><div class="lbl">危険レベル</div></div>
        <div class="stat-card"><div class="num" style="color:#e65100;"><?= $high_count ?></div><div class="lbl">高リスク</div></div>
        <div class="stat-card"><div class="num"><?= $avg_score ?></div><div class="lbl">平均スコア</div></div>
        <div class="stat-card"><div class="num" style="color:#c62828;font-size:20px;"><?= number_format($total_damage) ?>万</div><div class="lbl">累計想定被害額</div></div>
    </div>

    <!-- 一覧テーブル -->
    <div class="table-wrap">
        <?php if (empty($rows)): ?>
        <div class="no-data">診断データがありません</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>診断日時</th>
                    <th>企業名</th>
                    <th>担当者</th>
                    <th>業種</th>
                    <th>従業員</th>
                    <th>スコア</th>
                    <th>リスク</th>
                    <th>想定被害額（最大）</th>
                    <th>DDHBOX推奨プラン</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
            <?php
                $sc = intval($row['security_score'] ?? 0);
                $fill_color = $sc >= 70 ? '#2e7d32' : ($sc >= 50 ? '#f57f17' : '#c62828');
                $industry_label = INDUSTRY_LABELS[$row['industry']] ?? $row['industry'];
            ?>
            <tr>
                <td style="white-space:nowrap;color:#888;">
                    <?= htmlspecialchars(substr($row['created_at'] ?? '', 0, 16)) ?>
                </td>
                <td style="font-weight:bold;">
                    <?= htmlspecialchars($row['company_name'] ?? '-') ?>
                </td>
                <td><?= htmlspecialchars($row['contact_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($industry_label) ?></td>
                <td><?= htmlspecialchars($row['employees'] ?? '-') ?>名</td>
                <td>
                    <div style="font-weight:bold;"><?= $sc ?>点</div>
                    <div class="score-bar">
                        <div class="score-fill" style="width:<?= $sc ?>%;background:<?= $fill_color ?>;"></div>
                    </div>
                </td>
                <td><?= risk_badge($row['risk_level'] ?? '') ?></td>
                <td class="damage-amount">
                    <?= number_format(intval($row['damage_max'] ?? 0)) ?>万円
                </td>
                <td><?= htmlspecialchars($row['ddhbox_plan_name'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
