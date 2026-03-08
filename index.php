<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>企業セキュリティ診断ツール</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>企業セキュリティ診断ツール</h1>
            <p>貴社のセキュリティリスクを診断し、想定被害額をご提示します</p>
        </header>

        <div class="card">
            <h2>Step 1：企業情報の入力</h2>
            <form action="assessment.php" method="POST">
                <div class="form-group">
                    <label for="company_name">企業名 <span class="required">*</span></label>
                    <input type="text" id="company_name" name="company_name" required placeholder="例：株式会社〇〇">
                </div>

                <div class="form-group">
                    <label for="industry">業種 <span class="required">*</span></label>
                    <select id="industry" name="industry" required>
                        <option value="">選択してください</option>
                        <option value="finance">金融・保険</option>
                        <option value="medical">医療・ヘルスケア</option>
                        <option value="retail">小売・EC</option>
                        <option value="manufacturing">製造業</option>
                        <option value="it">IT・通信</option>
                        <option value="government">官公庁・自治体</option>
                        <option value="education">教育</option>
                        <option value="other">その他</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="employee_count">従業員数 <span class="required">*</span></label>
                    <select id="employee_count" name="employee_count" required>
                        <option value="">選択してください</option>
                        <option value="small">1〜50名</option>
                        <option value="medium">51〜300名</option>
                        <option value="large">301〜1000名</option>
                        <option value="enterprise">1001名以上</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="annual_revenue">年間売上高（概算）</label>
                    <select id="annual_revenue" name="annual_revenue">
                        <option value="">選択してください</option>
                        <option value="1">1億円未満</option>
                        <option value="5">1〜5億円</option>
                        <option value="10">5〜10億円</option>
                        <option value="50">10〜50億円</option>
                        <option value="100">50〜100億円</option>
                        <option value="500">100億円以上</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="personal_data_count">保有する個人情報件数（概算）</label>
                    <select id="personal_data_count" name="personal_data_count">
                        <option value="">選択してください</option>
                        <option value="none">保有していない</option>
                        <option value="small">1〜1,000件</option>
                        <option value="medium">1,001〜10,000件</option>
                        <option value="large">10,001〜100,000件</option>
                        <option value="xlarge">100,001件以上</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="contact_name">担当者名</label>
                    <input type="text" id="contact_name" name="contact_name" placeholder="例：山田 太郎">
                </div>

                <div class="btn-wrap">
                    <button type="submit" class="btn-primary">セキュリティ診断を開始する →</button>
                </div>
            </form>
        </div>

        <footer>
            <p>※ 本診断は業界統計データをもとにしたリスク試算です。実際の被害額は環境によって異なります。</p>
        </footer>
    </div>
</body>
</html>
