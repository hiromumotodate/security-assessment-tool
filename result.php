<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>診断結果 | 企業セキュリティ診断ツール</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/calculator.php';

$company_name    = htmlspecialchars($_POST['company_name'] ?? '貴社', ENT_QUOTES, 'UTF-8');
$industry        = $_POST['industry'] ?? 'other';
$employee_count  = $_POST['employee_count'] ?? 'small';
$contact_name    = htmlspecialchars($_POST['contact_name'] ?? '', ENT_QUOTES, 'UTF-8');

$industry_labels = [
    'finance'       => '金融・保険',
    'medical'       => '医療・ヘルスケア',
    'retail'        => '小売・EC',
    'manufacturing' => '製造業',
    'it'            => 'IT・通信',
    'government'    => '官公庁・自治体',
    'education'     => '教育',
    'other'         => 'その他',
];

$employee_labels = [
    'small'      => '1〜50名',
    'medium'     => '51〜300名',
    'large'      => '301〜1000名',
    'enterprise' => '1001名以上',
];

$score_result = calculate_security_score($_POST);
$score        = $score_result['score'];

$_POST['security_score'] = $score;
$damage       = calculate_damage($_POST);
$recs         = get_recommendations($_POST);

$risk_colors = [
    'low'      => '#27ae60',
    'medium'   => '#f39c12',
    'high'     => '#e74c3c',
    'critical' => '#8e44ad',
];
$risk_color = $risk_colors[$score_result['risk_level']] ?? '#e74c3c';

$today = date('Y年m月d日');
?>

    <div class="container">
        <header>
            <h1>企業セキュリティ診断ツール</h1>
            <p><?= $company_name ?> 様 診断結果レポート</p>
        </header>

        <div class="progress-bar">
            <div class="step completed">1. 企業情報</div>
            <div class="step completed">2. セキュリティ診断</div>
            <div class="step active">3. 診断結果</div>
        </div>

        <!-- 診断サマリー -->
        <div class="card result-summary">
            <div class="report-header">
                <div>
                    <h2>セキュリティ診断レポート</h2>
                    <p><?= $company_name ?> ／ <?= $industry_labels[$industry] ?? '' ?> ／ <?= $employee_labels[$employee_count] ?? '' ?></p>
                    <p class="report-date">診断日：<?= $today ?><?= $contact_name ? '　担当：' . $contact_name : '' ?></p>
                </div>
                <div class="score-circle" style="border-color: <?= $risk_color ?>; color: <?= $risk_color ?>">
                    <span class="score-number"><?= $score ?></span>
                    <span class="score-label">/ 100</span>
                    <span class="risk-badge" style="background: <?= $risk_color ?>"><?= $score_result['risk_label'] ?></span>
                </div>
            </div>
        </div>

        <!-- 想定被害額 -->
        <div class="card damage-card">
            <h2>想定される最大被害額</h2>
            <div class="damage-total">
                <span class="damage-amount">約 <?= number_format($damage['total']) ?> 万円</span>
                <span class="damage-note">サイバー攻撃を受けた場合の推計被害額</span>
            </div>

            <h3>被害シナリオ別内訳</h3>
            <div class="breakdown-list">
                <?php foreach ($damage['breakdown'] as $item): ?>
                <div class="breakdown-item">
                    <div class="breakdown-label">
                        <span class="risk-dot risk-<?= $item['risk'] ?>"></span>
                        <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="breakdown-amount">約 <?= number_format($item['amount']) ?> 万円</div>
                </div>
                <?php endforeach; ?>
            </div>

            <p class="damage-note-small">※ IPA統計・Ponemon Institute調査・個人情報保護委員会ガイドラインをもとに算出した推計値です</p>
        </div>

        <!-- 改善推奨事項 -->
        <?php if (!empty($recs)): ?>
        <div class="card">
            <h2>セキュリティ強化の推奨事項</h2>
            <p class="subtitle">以下の対策を実施することで、リスクを大幅に低減できます</p>
            <div class="rec-list">
                <?php foreach ($recs as $rec): ?>
                <div class="rec-item priority-<?= $rec['priority'] ?>">
                    <span class="priority-badge">
                        <?= $rec['priority'] === 'high' ? '優先度：高' : ($rec['priority'] === 'medium' ? '優先度：中' : '優先度：低') ?>
                    </span>
                    <p><?= htmlspecialchars($rec['text'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- CTA -->
        <div class="card cta-card">
            <h2>次のステップ</h2>
            <p>本診断結果をもとに、貴社に最適なセキュリティハードウェアソリューションをご提案いたします。</p>
            <div class="cta-actions">
                <button onclick="window.print()" class="btn-primary">レポートを印刷・PDF保存</button>
                <a href="index.php" class="btn-secondary">別の企業を診断する</a>
            </div>
        </div>

        <footer>
            <p>※ 本診断は業界統計データをもとにしたリスク試算です。実際の被害額は環境によって異なります。</p>
        </footer>
    </div>
</body>
</html>
