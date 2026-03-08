<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>診断結果 | 企業セキュリティ診断ツール</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+JP:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/calculator.php';
require_once __DIR__ . '/includes/case_matcher.php';
require_once __DIR__ . '/includes/supabase.php';

$company_name   = htmlspecialchars($_POST['company_name'] ?? '貴社', ENT_QUOTES, 'UTF-8');
$industry       = $_POST['industry'] ?? 'other';
$employee_count = $_POST['employee_count'] ?? 'small';
$employees      = intval($_POST['employees'] ?? 0);
$pc_count       = intval($_POST['pc_count'] ?? 10);
$has_personal   = !empty($_POST['has_personal_info']) && $_POST['has_personal_info'] !== '0';
$contact_name   = htmlspecialchars($_POST['contact_name'] ?? '', ENT_QUOTES, 'UTF-8');

// 計算実行
$score_result  = calculate_security_score($_POST);
$score         = $score_result['score'];
$_POST['security_score'] = $score;

$damage        = calculate_damage($_POST);
$recs          = get_recommendations($_POST);
$ddhbox_plan   = recommend_ddhbox_plan($pc_count);
$necfru        = estimate_necfru_cost($employees ?: 10);

// 事例マッチング
$weak_cats     = get_weak_categories($_POST);
$related_cases = get_related_cases($industry, $employee_count, $weak_cats, 3);

// Supabase に診断結果を保存（エラーでも画面表示は継続）
supabase_insert('assessments', [
    'company_name'     => $_POST['company_name'] ?? '',
    'contact_name'     => $_POST['contact_name'] ?? '',
    'industry'         => $industry,
    'employee_count'   => $employee_count,
    'employees'        => $employees,
    'pc_count'         => $pc_count,
    'has_personal_info'=> $has_personal,
    'security_score'   => $score,
    'risk_level'       => $score_result['risk_level'],
    'risk_label'       => $score_result['risk_label'],
    'damage_min'       => $damage['min'],
    'damage_center'    => $damage['center'],
    'damage_max'       => $damage['max'],
    'ddhbox_plan_name' => $ddhbox_plan['name'],
    'ddhbox_monthly'   => $ddhbox_plan['monthly'],
    'necfru_monthly'   => $necfru['monthly_total'],
    'answers'          => json_encode($_POST['answers'] ?? []),
    'category_scores'  => json_encode($score_result['category_scores']),
    'recommendations'  => json_encode($recs),
]);

// 費用対効果
$annual_cost   = $ddhbox_plan['annual'] + $necfru['annual_total'];
$roi_ratio     = $annual_cost > 0 ? round($damage['min'] / ($annual_cost / 10000), 1) : 0;

$today = date('Y年m月d日');

// レーダーチャート用データ
$chart_labels  = [];
$chart_scores  = [];
$chart_maxes   = [];
foreach ($score_result['category_scores'] as $cat) {
    $chart_labels[]  = $cat['label'];
    $chart_scores[]  = $cat['score'];
    $chart_maxes[]   = $cat['max'];
}

// DDHBOXの提案強度
$ddhbox_urgency = 'reference';
if ($score <= 59 || ($score_result['category_scores']['CAT-01']['rate'] ?? 100) < 60) {
    $ddhbox_urgency = 'urgent';
} elseif ($score <= 79) {
    $ddhbox_urgency = 'recommend';
}

// necfruの提案強度
$necfru_urgency = 'reference';
$cat03_rate = $score_result['category_scores']['CAT-03']['rate'] ?? 100;
$cat04_rate = $score_result['category_scores']['CAT-04']['rate'] ?? 100;
if ($cat03_rate < 50 || ($has_personal && $cat04_rate < 70)) {
    $necfru_urgency = 'urgent';
} elseif ($cat03_rate < 80) {
    $necfru_urgency = 'recommend';
}
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

        <!-- ① 診断サマリー -->
        <div class="card result-summary">
            <div class="report-header">
                <div class="report-meta">
                    <h2>セキュリティ診断レポート</h2>
                    <p><?= $company_name ?> ／ <?= htmlspecialchars(INDUSTRY_LABELS[$industry] ?? '', ENT_QUOTES, 'UTF-8') ?> ／ <?= htmlspecialchars(EMPLOYEE_DAMAGE_BASE[$employee_count]['label'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="report-date">診断日：<?= $today ?><?= $contact_name ? '　担当：' . $contact_name : '' ?></p>
                    <p class="risk-message" style="color:<?= $score_result['risk_color'] ?>"><?= htmlspecialchars($score_result['risk_message'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="score-circle" style="border-color:<?= $score_result['risk_color'] ?>; color:<?= $score_result['risk_color'] ?>">
                    <span class="score-number"><?= $score ?></span>
                    <span class="score-label">/ 100</span>
                    <span class="risk-badge" style="background:<?= $score_result['risk_color'] ?>"><?= $score_result['risk_label'] ?></span>
                </div>
            </div>
        </div>

        <!-- ② カテゴリ別レーダーチャート -->
        <div class="card">
            <h2>カテゴリ別セキュリティスコア</h2>
            <div class="chart-wrap">
                <canvas id="radarChart" width="380" height="320"></canvas>
            </div>
            <div class="category-scores">
                <?php foreach ($score_result['category_scores'] as $cat_id => $cat): ?>
                <div class="cat-score-item">
                    <span class="cat-icon"><?= $cat['icon'] ?></span>
                    <div class="cat-bar-wrap">
                        <div class="cat-bar-label"><?= htmlspecialchars($cat['label'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="cat-bar-track">
                            <div class="cat-bar-fill" style="width:<?= $cat['rate'] ?>%; background:<?php
                                if ($cat['rate'] >= 80) echo '#2e7d32';
                                elseif ($cat['rate'] >= 60) echo '#f9a825';
                                else echo '#c62828';
                            ?>"></div>
                        </div>
                    </div>
                    <span class="cat-score-num"><?= $cat['score'] ?>/<?= $cat['max'] ?>点</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ③ 想定被害額 -->
        <div class="card damage-card">
            <h2>想定される被害額レンジ</h2>
            <div class="damage-range">
                <div class="damage-range-item min">
                    <span class="damage-range-label">最小想定</span>
                    <span class="damage-range-value">約 <?= number_format($damage['min']) ?> 万円</span>
                </div>
                <div class="damage-range-arrow">〜</div>
                <div class="damage-range-item max">
                    <span class="damage-range-label">最大想定</span>
                    <span class="damage-range-value">約 <?= number_format($damage['max']) ?> 万円</span>
                </div>
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

        <!-- ④ DDHBOX提案 -->
        <div class="card product-card ddhbox-card urgency-<?= $ddhbox_urgency ?>">
            <div class="product-header">
                <div class="product-badge-wrap">
                    <?php if ($ddhbox_urgency === 'urgent'): ?>
                    <span class="urgency-badge urgent">⚠️ 強く推奨</span>
                    <?php elseif ($ddhbox_urgency === 'recommend'): ?>
                    <span class="urgency-badge recommend">✅ 推奨</span>
                    <?php else: ?>
                    <span class="urgency-badge reference">💡 参考提案</span>
                    <?php endif; ?>
                </div>
                <h2>DDHBOX ― 不正通信を出口で完全遮断</h2>
                <p class="product-sub">ネットワーク出口に繋ぐだけ。ランサムウェアのC2通信を全自動で遮断します</p>
            </div>

            <div class="plan-recommend">
                <div class="plan-box">
                    <span class="plan-label">貴社推奨プラン（PC<?= $pc_count ?>台）</span>
                    <span class="plan-name"><?= htmlspecialchars($ddhbox_plan['name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="plan-price-row">
                        <div class="plan-price-item">
                            <span class="plan-price-label">月額</span>
                            <span class="plan-price-value">¥<?= number_format($ddhbox_plan['monthly']) ?><small>/月</small></span>
                        </div>
                        <div class="plan-price-item">
                            <span class="plan-price-label">年額</span>
                            <span class="plan-price-value">¥<?= number_format($ddhbox_plan['annual']) ?><small>/年</small></span>
                        </div>
                        <div class="plan-price-item highlight">
                            <span class="plan-price-label">5年総額</span>
                            <span class="plan-price-value">¥<?= number_format($ddhbox_plan['annual'] * 5) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-features">
                <div class="feature-item">🔒 C2サーバリスト365日自動更新（ラック社JSOC）</div>
                <div class="feature-item">🏥 サイバー保険 <strong>年間300万円</strong> 自動付帯</div>
                <div class="feature-item">⚡ 工事不要・ネットワーク出口に繋ぐだけ</div>
                <div class="feature-item">📊 導入企業の <strong>約4社に1社（25.7%）</strong> で不正通信を検知</div>
                <div class="feature-item">🛟 インシデント後サポート（フォレンジック・データ復旧・弁護士相談）</div>
            </div>
        </div>

        <!-- ⑤ necfru MAM/DAM提案 -->
        <div class="card product-card necfru-card urgency-<?= $necfru_urgency ?>">
            <div class="product-header">
                <div class="product-badge-wrap">
                    <?php if ($necfru_urgency === 'urgent'): ?>
                    <span class="urgency-badge urgent">⚠️ 強く推奨</span>
                    <?php elseif ($necfru_urgency === 'recommend'): ?>
                    <span class="urgency-badge recommend">✅ 推奨</span>
                    <?php else: ?>
                    <span class="urgency-badge reference">💡 参考提案</span>
                    <?php endif; ?>
                </div>
                <h2>necfru MAM/DAM ― クラウドでデータを守る・探せる・続ける</h2>
                <p class="product-sub">消せないデータを低コストで長期保管。ランサムウェア被害後もデータを復元できます</p>
            </div>

            <div class="plan-recommend">
                <div class="plan-box necfru-box">
                    <span class="plan-label">貴社費用試算（従業員<?= $employees ?>名・約<?= number_format($necfru['storage_gb']) ?>GB）</span>
                    <div class="plan-price-row">
                        <div class="plan-price-item">
                            <span class="plan-price-label">基本料（ユーザー無制限）</span>
                            <span class="plan-price-value">¥<?= number_format($necfru['monthly_base']) ?><small>/月</small></span>
                        </div>
                        <div class="plan-price-item">
                            <span class="plan-price-label">ストレージ概算</span>
                            <span class="plan-price-value">¥<?= number_format($necfru['storage_cost']) ?><small>/月</small></span>
                        </div>
                        <div class="plan-price-item highlight">
                            <span class="plan-price-label">月額合計（概算）</span>
                            <span class="plan-price-value">¥<?= number_format($necfru['monthly_total']) ?><small>/月</small></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-features">
                <div class="feature-item">☁️ クラウド分散保管・90日ゴミ箱でデータ消失から復旧</div>
                <div class="feature-item">🔍 タグ・メタデータで瞬時に検索・管理</div>
                <div class="feature-item">🔐 権限管理・操作ログで内部不正リスクを低減</div>
                <div class="feature-item">🔗 外部共有URLでUSB/メール誤送信リスクを排除</div>
                <div class="feature-item">📦 HOT/COLDストレージでコスト最適化（HOT 1.2円/GB）</div>
            </div>
        </div>

        <!-- ⑥ 費用対効果サマリー -->
        <div class="card roi-card">
            <h2>費用対効果サマリー</h2>
            <div class="roi-grid">
                <div class="roi-item risk">
                    <span class="roi-label">想定被害額（最小）</span>
                    <span class="roi-value">約 <?= number_format($damage['min']) ?> 万円</span>
                </div>
                <div class="roi-vs">vs</div>
                <div class="roi-item cost">
                    <span class="roi-label">年間導入費用（概算）</span>
                    <span class="roi-value">約 <?= number_format(round($annual_cost / 10000)) ?> 万円</span>
                    <span class="roi-detail">DDHBOX <?= number_format(round($ddhbox_plan['annual'] / 10000)) ?>万円 + necfru <?= number_format(round($necfru['annual_total'] / 10000)) ?>万円</span>
                </div>
            </div>
            <?php if ($roi_ratio > 0): ?>
            <div class="roi-message">
                想定被害額（最小）は年間導入費用の <strong><?= $roi_ratio ?>倍</strong> です。<br>
                月額 <?= number_format(round($annual_cost / 12 / 10000)) ?> 万円の投資で、<?= number_format($damage['min']) ?> 万円以上のリスクに備えることができます。
            </div>
            <?php endif; ?>
        </div>

        <!-- ⑦ 改善推奨事項 -->
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

        <!-- ⑧ 実際の被害事例 -->
        <?php if (!empty($related_cases)): ?>
        <div class="card cases-card">
            <h2>実際の被害事例：同業種・類似企業での事件</h2>
            <p class="subtitle">貴社と類似した業種・規模・セキュリティ状況の企業で実際に起きた事例です</p>
            <div class="cases-list">
                <?php foreach ($related_cases as $case):
                    $comment = get_comparison_comment($damage['max'], $case);
                ?>
                <div class="case-item">
                    <div class="case-header">
                        <span class="case-icon"><?= $case['icon'] ?></span>
                        <div class="case-title-wrap">
                            <span class="case-year"><?= $case['year'] ?>年</span>
                            <h3 class="case-title"><?= htmlspecialchars($case['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <span class="case-org"><?= htmlspecialchars($case['org'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="case-damage-wrap">
                            <span class="case-damage-label">実際の被害額</span>
                            <span class="case-damage-amount"><?= htmlspecialchars($case['damage_label'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <p class="case-description"><?= htmlspecialchars($case['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="case-footer">
                        <div class="case-lesson">
                            <span class="lesson-label">教訓</span>
                            <?= htmlspecialchars($case['lesson'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="case-comparison">
                            <span class="comparison-icon">⚠️</span>
                            <?= htmlspecialchars($comment, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    <p class="case-source">出典：<?= htmlspecialchars($case['source'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="cases-summary">
                <p>上記事例の共通点：<strong>不正通信の「出口対策」が未整備</strong>だったため、侵入後の情報流出・被害拡大を防げませんでした。</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- ⑨ アクション -->
        <div class="card cta-card">
            <h2>次のステップ</h2>
            <p>本診断結果をもとに、DDHBOXとnecfru MAM/DAMの導入をご検討ください。</p>
            <div class="cta-actions">
                <button onclick="window.print()" class="btn-primary">レポートを印刷・PDF保存</button>
                <a href="index.php" class="btn-secondary">別の企業を診断する</a>
            </div>
        </div>

        <footer>
            <p>※ 本診断は業界統計データをもとにしたリスク試算です。実際の被害額は環境によって異なります。</p>
        </footer>
    </div>

<script>
// レーダーチャート描画
const ctx = document.getElementById('radarChart').getContext('2d');
new Chart(ctx, {
    type: 'radar',
    data: {
        labels: <?= json_encode($chart_labels, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
            label: '現状スコア',
            data: <?= json_encode($chart_scores) ?>,
            backgroundColor: 'rgba(57, 73, 171, 0.2)',
            borderColor: 'rgba(57, 73, 171, 0.9)',
            borderWidth: 2,
            pointBackgroundColor: 'rgba(57, 73, 171, 1)',
            pointRadius: 4,
        }, {
            label: '満点',
            data: <?= json_encode($chart_maxes) ?>,
            backgroundColor: 'rgba(200, 200, 200, 0.1)',
            borderColor: 'rgba(200, 200, 200, 0.5)',
            borderWidth: 1,
            borderDash: [4, 4],
            pointRadius: 0,
        }]
    },
    options: {
        responsive: true,
        scales: {
            r: {
                beginAtZero: true,
                ticks: { display: false },
                grid: { color: 'rgba(0,0,0,0.08)' },
                pointLabels: { font: { size: 12 } }
            }
        },
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 12 } } }
        }
    }
});

// カテゴリバーのアニメーション
document.querySelectorAll('.cat-bar-fill').forEach(bar => {
    const w = bar.style.width;
    bar.style.width = '0';
    setTimeout(() => { bar.style.transition = 'width 0.8s ease'; bar.style.width = w; }, 100);
});
</script>
</body>
</html>
