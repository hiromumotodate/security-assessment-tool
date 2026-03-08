<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>セキュリティチェックリスト | 企業セキュリティ診断ツール</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+JP:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
require_once __DIR__ . '/includes/config.php';

$company_name  = htmlspecialchars($_POST['company_name'] ?? '', ENT_QUOTES, 'UTF-8');
$industry      = htmlspecialchars($_POST['industry'] ?? '', ENT_QUOTES, 'UTF-8');
$employee_count = htmlspecialchars($_POST['employee_count'] ?? '', ENT_QUOTES, 'UTF-8');
$employees     = htmlspecialchars($_POST['employees'] ?? '', ENT_QUOTES, 'UTF-8');
$pc_count      = htmlspecialchars($_POST['pc_count'] ?? '', ENT_QUOTES, 'UTF-8');
$has_personal_info = htmlspecialchars($_POST['has_personal_info'] ?? '0', ENT_QUOTES, 'UTF-8');
$contact_name  = htmlspecialchars($_POST['contact_name'] ?? '', ENT_QUOTES, 'UTF-8');
?>
    <div class="container">
        <header>
            <h1>企業セキュリティ診断ツール</h1>
            <p><?= $company_name ?> のセキュリティ診断</p>
        </header>

        <div class="progress-bar">
            <div class="step completed">1. 企業情報</div>
            <div class="step active">2. セキュリティ診断</div>
            <div class="step">3. 診断結果</div>
        </div>

        <div class="card">
            <h2>Step 2：セキュリティ状況のチェック</h2>
            <p class="subtitle">現在の対策状況について、各設問にお答えください（全20問）</p>

            <form action="result.php" method="POST">
                <!-- 企業情報を引き継ぐ -->
                <input type="hidden" name="company_name" value="<?= $company_name ?>">
                <input type="hidden" name="industry" value="<?= $industry ?>">
                <input type="hidden" name="employee_count" value="<?= $employee_count ?>">
                <input type="hidden" name="employees" value="<?= $employees ?>">
                <input type="hidden" name="pc_count" value="<?= $pc_count ?>">
                <input type="hidden" name="has_personal_info" value="<?= $has_personal_info ?>">
                <input type="hidden" name="contact_name" value="<?= $contact_name ?>">

                <?php foreach (ASSESSMENT_CATEGORIES as $cat_id => $cat): ?>
                <div class="check-category">
                    <h3><?= $cat['icon'] ?> <?= htmlspecialchars($cat['label'], ENT_QUOTES, 'UTF-8') ?>
                        <span class="cat-points">（配点 <?= $cat['points'] ?>点）</span>
                    </h3>
                    <?php foreach ($cat['questions'] as $q_key => $q_text): ?>
                    <div class="check-item">
                        <label><?= htmlspecialchars($q_text, ENT_QUOTES, 'UTF-8') ?></label>
                        <div class="radio-group">
                            <label class="radio-yes"><input type="radio" name="answers[<?= $q_key ?>]" value="yes" required> はい（対策済み）</label>
                            <label class="radio-unknown"><input type="radio" name="answers[<?= $q_key ?>]" value="unknown"> わからない</label>
                            <label class="radio-no"><input type="radio" name="answers[<?= $q_key ?>]" value="no"> いいえ（未対策）</label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

                <div class="btn-wrap">
                    <a href="index.php" class="btn-secondary">← 戻る</a>
                    <button type="submit" class="btn-primary">診断結果を見る →</button>
                </div>
            </form>
        </div>

        <footer>
            <p>※ 本診断は業界統計データをもとにしたリスク試算です。実際の被害額は環境によって異なります。</p>
        </footer>
    </div>
</body>
</html>
