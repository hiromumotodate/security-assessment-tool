<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>セキュリティチェックリスト | 企業セキュリティ診断ツール</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$company_name      = htmlspecialchars($_POST['company_name'] ?? '', ENT_QUOTES, 'UTF-8');
$industry          = htmlspecialchars($_POST['industry'] ?? '', ENT_QUOTES, 'UTF-8');
$employee_count    = htmlspecialchars($_POST['employee_count'] ?? '', ENT_QUOTES, 'UTF-8');
$annual_revenue    = htmlspecialchars($_POST['annual_revenue'] ?? '', ENT_QUOTES, 'UTF-8');
$personal_data_count = htmlspecialchars($_POST['personal_data_count'] ?? '', ENT_QUOTES, 'UTF-8');
$contact_name      = htmlspecialchars($_POST['contact_name'] ?? '', ENT_QUOTES, 'UTF-8');
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
            <p class="subtitle">現在の対策状況について、当てはまるものを選択してください</p>

            <form action="result.php" method="POST">
                <!-- 企業情報を引き継ぐ -->
                <input type="hidden" name="company_name" value="<?= $company_name ?>">
                <input type="hidden" name="industry" value="<?= $industry ?>">
                <input type="hidden" name="employee_count" value="<?= $employee_count ?>">
                <input type="hidden" name="annual_revenue" value="<?= $annual_revenue ?>">
                <input type="hidden" name="personal_data_count" value="<?= $personal_data_count ?>">
                <input type="hidden" name="contact_name" value="<?= $contact_name ?>">

                <!-- カテゴリ1: 物理セキュリティ -->
                <div class="check-category">
                    <h3>物理セキュリティ</h3>
                    <div class="check-item">
                        <label>サーバー室・機器室への入退室管理は行っていますか？</label>
                        <div class="radio-group">
                            <label><input type="radio" name="physical_access" value="2" required> 実施済み（ICカード等）</label>
                            <label><input type="radio" name="physical_access" value="1"> 一部実施</label>
                            <label><input type="radio" name="physical_access" value="0"> 未実施</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <label>PC・モバイル端末の盗難・紛失対策（暗号化・リモートワイプ等）をしていますか？</label>
                        <div class="radio-group">
                            <label><input type="radio" name="device_protection" value="2" required> 実施済み</label>
                            <label><input type="radio" name="device_protection" value="1"> 一部実施</label>
                            <label><input type="radio" name="device_protection" value="0"> 未実施</label>
                        </div>
                    </div>
                </div>

                <!-- カテゴリ2: ネットワークセキュリティ -->
                <div class="check-category">
                    <h3>ネットワークセキュリティ</h3>
                    <div class="check-item">
                        <label>ファイアウォール・UTMを導入していますか？</label>
                        <div class="radio-group">
                            <label><input type="radio" name="firewall" value="2" required> 導入済み（最新版）</label>
                            <label><input type="radio" name="firewall" value="1"> 導入済み（古い）</label>
                            <label><input type="radio" name="firewall" value="0"> 未導入</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <label>VPNを利用したリモートアクセス管理をしていますか？</label>
                        <div class="radio-group">
                            <label><input type="radio" name="vpn" value="2" required> 実施済み</label>
                            <label><input type="radio" name="vpn" value="1"> 一部実施</label>
                            <label><input type="radio" name="vpn" value="0"> 未実施</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <label>Wi-Fiは社員用と来客用でネットワークを分離していますか？</label>
                        <div class="radio-group">
                            <label><input type="radio" name="wifi_separation" value="2" required> 分離済み</label>
                            <label><input type="radio" name="wifi_separation" value="1"> 一部分離</label>
                            <label><input type="radio" name="wifi_separation" value="0"> 未分離</label>
                        </div>
                    </div>
                </div>

                <!-- カテゴリ3: エンドポイントセキュリティ -->
                <div class="check-category">
                    <h3>エンドポイントセキュリティ</h3>
                    <div class="check-item">
                        <label>ウイルス対策ソフト（EDR含む）を全端末に導入していますか？</label>
                        <div class="radio-group">
                            <label><input type="radio" name="antivirus" value="2" required> 全端末導入済み</label>
                            <label><input type="radio" name="antivirus" value="1"> 一部導入</label>
                            <label><input type="radio" name="antivirus" value="0"> 未導入</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <label>OSやソフトウェアのセキュリティパッチを定期的に適用していますか？</label>
                        <div class="radio-group">
                            <label><input type="radio" name="patch_management" value="2" required> 定期的に実施</label>
                            <label><input type="radio" name="patch_management" value="1"> 不定期に実施</label>
                            <label><input type="radio" name="patch_management" value="0"> ほぼ未実施</label>
                        </div>
                    </div>
                </div>

                <!-- カテゴリ4: アクセス管理 -->
                <div class="check-category">
                    <h3>アクセス管理・認証</h3>
                    <div class="check-item">
                        <label>多要素認証（MFA）を導入していますか？</label>
                        <div class="radio-group">
                            <label><input type="radio" name="mfa" value="2" required> 主要システムに導入済み</label>
                            <label><input type="radio" name="mfa" value="1"> 一部導入</label>
                            <label><input type="radio" name="mfa" value="0"> 未導入</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <label>退職者・異動者のアカウント削除・権限変更を速やかに行っていますか？</label>
                        <div class="radio-group">
                            <label><input type="radio" name="account_management" value="2" required> 即日対応</label>
                            <label><input type="radio" name="account_management" value="1"> 数日以内に対応</label>
                            <label><input type="radio" name="account_management" value="0"> 対応が遅れることがある</label>
                        </div>
                    </div>
                </div>

                <!-- カテゴリ5: バックアップ・事業継続 -->
                <div class="check-category">
                    <h3>バックアップ・事業継続</h3>
                    <div class="check-item">
                        <label>重要データのバックアップを定期的に取得していますか？</label>
                        <div class="radio-group">
                            <label><input type="radio" name="backup" value="2" required> 日次でオフサイト保存</label>
                            <label><input type="radio" name="backup" value="1"> 週次以上で取得</label>
                            <label><input type="radio" name="backup" value="0"> 不定期または未実施</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <label>サイバー攻撃を受けた場合のインシデント対応手順が文書化されていますか？</label>
                        <div class="radio-group">
                            <label><input type="radio" name="incident_response" value="2" required> 文書化・訓練済み</label>
                            <label><input type="radio" name="incident_response" value="1"> 文書化のみ</label>
                            <label><input type="radio" name="incident_response" value="0"> 未整備</label>
                        </div>
                    </div>
                </div>

                <!-- カテゴリ6: 教育・意識向上 -->
                <div class="check-category">
                    <h3>セキュリティ教育</h3>
                    <div class="check-item">
                        <label>従業員向けセキュリティ教育・フィッシング訓練を実施していますか？</label>
                        <div class="radio-group">
                            <label><input type="radio" name="security_training" value="2" required> 年1回以上実施</label>
                            <label><input type="radio" name="security_training" value="1"> 実施したことがある</label>
                            <label><input type="radio" name="security_training" value="0"> 未実施</label>
                        </div>
                    </div>
                </div>

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
