# UI/UX改善 実装計画

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 企業セキュリティ診断ツールのUI/UXを、クリーン＆ミニマルなデザインに全面刷新し、チェックリストのステップ分割・フォームバリデーション・印刷対応を実装する

**Architecture:** PHPバックエンドロジックは一切変更しない。CSS全面リライト＋各PHPファイルのHTML構造調整＋インラインJS追加で実現する。外部JSファイルは作成せず、現状の構成を維持する。

**Tech Stack:** PHP（テンプレート部分のみ変更）, CSS3（CSS変数）, Vanilla JS, Chart.js（CDN維持）, Google Fonts（Inter + Noto Sans JP）

---

## Task 1: CSS全面リライト — デザイントークン＆ベーススタイル

**Files:**
- Rewrite: `css/style.css`

**Step 1: CSS変数・リセット・ベーススタイルを定義**

`css/style.css` を全面リライトする。以下のセクション構成で記述する:

```css
/* === CSS Variables === */
:root {
  /* Colors */
  --color-bg: #fafafa;
  --color-bg-card: #ffffff;
  --color-bg-subtle: #f5f5f5;
  --color-text: #171717;
  --color-text-secondary: #666666;
  --color-text-muted: #a3a3a3;
  --color-accent: #1a237e;
  --color-accent-light: #e8eaf6;
  --color-accent-hover: #283593;
  --color-border: #e5e5e5;
  --color-error: #ef4444;
  --color-success: #16a34a;
  --color-warning: #f59e0b;
  --color-danger: #dc2626;
  --color-danger-bg: #fef2f2;

  /* Typography */
  --font-family: 'Inter', 'Noto Sans JP', system-ui, sans-serif;
  --font-size-sm: 14px;
  --font-size-base: 16px;
  --font-size-lg: 20px;
  --font-size-xl: 24px;
  --font-size-2xl: 32px;

  /* Spacing (8px grid) */
  --space-1: 8px;
  --space-2: 16px;
  --space-3: 24px;
  --space-4: 32px;
  --space-6: 48px;
  --space-8: 64px;

  /* Misc */
  --radius: 8px;
  --radius-sm: 4px;
  --shadow: 0 1px 3px rgba(0,0,0,0.08);
  --transition: 150ms ease-out;
}

/* === Reset === */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* === Base === */
body {
  font-family: var(--font-family);
  background: var(--color-bg);
  color: var(--color-text);
  line-height: 1.6;
  font-size: var(--font-size-base);
  -webkit-font-smoothing: antialiased;
}

.container {
  max-width: 820px;
  margin: 0 auto;
  padding: var(--space-3) var(--space-2) var(--space-8);
}
```

**Step 2: ヘッダー・プログレスバーのスタイル**

```css
/* === Header === */
header {
  text-align: center;
  padding: var(--space-6) 0 var(--space-4);
}
header h1 {
  font-size: var(--font-size-xl);
  font-weight: 600;
  color: var(--color-text);
  margin-bottom: var(--space-1);
  letter-spacing: -0.02em;
}
header p {
  color: var(--color-text-secondary);
  font-size: var(--font-size-sm);
}

/* === Progress === */
/* ドット＆ライン型のステッパー */
.progress-bar {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0;
  margin-bottom: var(--space-4);
  padding: 0 var(--space-4);
}
.step {
  display: flex;
  align-items: center;
  gap: var(--space-1);
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
  font-weight: 500;
  padding: 0;
  background: none;
  white-space: nowrap;
}
.step::before {
  content: '';
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--color-border);
  flex-shrink: 0;
}
.step-line {
  width: 48px;
  height: 2px;
  background: var(--color-border);
  margin: 0 var(--space-1);
}
.step-line.completed { background: var(--color-accent); }
.step.completed { color: var(--color-accent); }
.step.completed::before { background: var(--color-accent); }
.step.active { color: var(--color-text); font-weight: 600; }
.step.active::before {
  background: transparent;
  border: 2px solid var(--color-accent);
}
```

注意: HTML側でプログレスバーのマークアップも変更が必要（step間に `.step-line` 要素を追加）。これはTask 3〜5で各PHPファイル改修時に対応する。

**Step 3: カード・見出し・フォームのスタイル**

```css
/* === Card === */
.card {
  background: var(--color-bg-card);
  border-radius: var(--radius);
  border: 1px solid var(--color-border);
  padding: var(--space-4);
  margin-bottom: var(--space-3);
}
.card h2 {
  font-size: var(--font-size-lg);
  font-weight: 600;
  color: var(--color-text);
  margin-bottom: var(--space-2);
  padding-bottom: var(--space-2);
  border-bottom: 1px solid var(--color-border);
}
.card h3 {
  font-size: var(--font-size-base);
  font-weight: 600;
  color: var(--color-text);
  margin: var(--space-3) 0 var(--space-1);
}
.subtitle {
  color: var(--color-text-secondary);
  font-size: var(--font-size-sm);
  margin-bottom: var(--space-3);
}

/* === Form === */
.form-group {
  margin-bottom: var(--space-3);
}
.form-group label {
  display: block;
  font-weight: 500;
  font-size: var(--font-size-sm);
  margin-bottom: var(--space-1);
  color: var(--color-text);
}
.form-group input[type="text"],
.form-group input[type="number"],
.form-group select {
  width: 100%;
  height: 40px;
  padding: 0 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  font-size: var(--font-size-sm);
  color: var(--color-text);
  background: var(--color-bg-card);
  transition: border-color var(--transition), box-shadow var(--transition);
}
.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: var(--color-accent);
  box-shadow: 0 0 0 2px rgba(26, 35, 126, 0.15);
}
.form-group input.error,
.form-group select.error {
  border-color: var(--color-error);
}
.field-error {
  font-size: 12px;
  color: var(--color-error);
  margin-top: 4px;
  display: none;
}
.field-error.show { display: block; }
.required { color: var(--color-error); font-weight: 400; }
.form-row { display: flex; gap: var(--space-3); }
.form-row .form-group { flex: 1; }
.field-note { font-size: 12px; color: var(--color-text-muted); display: block; margin-top: 4px; }
```

**Step 4: チェックリスト・ラジオボタンのスタイル**

```css
/* === Check Category === */
.check-category {
  margin-bottom: var(--space-3);
  padding: var(--space-3);
  background: var(--color-bg-subtle);
  border-radius: var(--radius);
  border: 1px solid var(--color-border);
}
.check-category h3 {
  color: var(--color-text);
  font-size: var(--font-size-base);
  margin: 0 0 var(--space-2);
  display: flex;
  align-items: center;
  gap: var(--space-1);
}
.cat-points { font-size: 12px; font-weight: 400; color: var(--color-text-muted); }

.check-item {
  margin-bottom: var(--space-2);
  padding: var(--space-2);
  background: var(--color-bg-card);
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  transition: border-color var(--transition);
}
.check-item.answered {
  border-color: var(--color-accent);
  background: rgba(26, 35, 126, 0.02);
}
.check-item label:first-child {
  display: block;
  font-size: var(--font-size-sm);
  font-weight: 500;
  color: var(--color-text);
  margin-bottom: var(--space-1);
}
.radio-group {
  display: flex;
  gap: var(--space-2);
  flex-wrap: wrap;
}
.radio-group label {
  font-size: var(--font-size-sm);
  font-weight: 400;
  color: var(--color-text-secondary);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  border-radius: var(--radius-sm);
  transition: background var(--transition);
  min-height: 44px; /* タッチターゲット */
}
.radio-group label:hover { background: var(--color-bg-subtle); }
.radio-group input[type="radio"] { accent-color: var(--color-accent); }
.radio-yes input { accent-color: var(--color-success); }
.radio-unknown input { accent-color: var(--color-warning); }
.radio-no input { accent-color: var(--color-danger); }

/* === Step Progress (assessment mini progress) === */
.step-indicator {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--space-3);
  padding: var(--space-2) var(--space-3);
  background: var(--color-bg-card);
  border-radius: var(--radius);
  border: 1px solid var(--color-border);
}
.step-indicator-label { font-size: var(--font-size-sm); font-weight: 500; color: var(--color-text); }
.step-indicator-bar {
  flex: 1;
  height: 4px;
  background: var(--color-border);
  border-radius: 2px;
  margin: 0 var(--space-2);
  overflow: hidden;
}
.step-indicator-fill {
  height: 100%;
  background: var(--color-accent);
  border-radius: 2px;
  transition: width 300ms ease-out;
}
.step-indicator-count { font-size: 12px; color: var(--color-text-muted); }
```

**Step 5: ボタンのスタイル**

```css
/* === Buttons === */
.btn-wrap {
  display: flex;
  justify-content: flex-end;
  gap: var(--space-2);
  margin-top: var(--space-4);
}
.btn-primary {
  background: var(--color-accent);
  color: #fff;
  border: none;
  padding: 10px var(--space-3);
  border-radius: var(--radius-sm);
  font-size: var(--font-size-sm);
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  transition: background var(--transition);
  min-height: 44px;
}
.btn-primary:hover { background: var(--color-accent-hover); }
.btn-primary:focus-visible { outline: 2px solid var(--color-accent); outline-offset: 2px; }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-secondary {
  background: var(--color-bg-card);
  color: var(--color-accent);
  border: 1px solid var(--color-border);
  padding: 10px var(--space-3);
  border-radius: var(--radius-sm);
  font-size: var(--font-size-sm);
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  transition: background var(--transition), border-color var(--transition);
  min-height: 44px;
}
.btn-secondary:hover { background: var(--color-bg-subtle); border-color: var(--color-accent); }
.btn-secondary:focus-visible { outline: 2px solid var(--color-accent); outline-offset: 2px; }
```

**Step 6: 結果画面のスタイル（サマリー・スコア・被害額）**

```css
/* === Result Summary === */
.result-summary {
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
}
.report-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--space-3);
}
.report-meta { flex: 1; }
.report-meta h2 { border-bottom: none; padding-bottom: 0; margin-bottom: var(--space-1); }
.report-date { font-size: 12px; color: var(--color-text-muted); margin-top: var(--space-1); }
.risk-message { font-size: var(--font-size-base); font-weight: 600; margin-top: var(--space-1); }

.score-circle {
  min-width: 120px;
  text-align: center;
  border: 6px solid;
  border-radius: 50%;
  width: 120px;
  height: 120px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: var(--color-bg-card);
  position: relative;
  flex-shrink: 0;
}
.score-number { font-size: var(--font-size-2xl); font-weight: 700; line-height: 1; }
.score-label { font-size: 12px; color: var(--color-text-muted); }
.risk-badge {
  position: absolute;
  bottom: -12px;
  left: 50%;
  transform: translateX(-50%);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  padding: 2px 10px;
  border-radius: 10px;
  white-space: nowrap;
}

/* === Radar Chart === */
.chart-wrap { display: flex; justify-content: center; margin: var(--space-2) 0 var(--space-3); }
.chart-wrap canvas { max-width: 380px; }

/* === Category Score Bars === */
.category-scores { display: flex; flex-direction: column; gap: var(--space-1); }
.cat-score-item { display: flex; align-items: center; gap: var(--space-1); }
.cat-icon { font-size: var(--font-size-lg); width: 28px; text-align: center; flex-shrink: 0; }
.cat-bar-wrap { flex: 1; }
.cat-bar-label { font-size: 12px; color: var(--color-text-secondary); margin-bottom: 2px; }
.cat-bar-track { height: 8px; background: var(--color-bg-subtle); border-radius: 4px; overflow: hidden; }
.cat-bar-fill { height: 100%; border-radius: 4px; transition: width 800ms ease-out; }
.cat-score-num { font-size: 12px; font-weight: 600; color: var(--color-text); white-space: nowrap; width: 60px; text-align: right; }

/* === Damage === */
.damage-range {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-3);
  background: var(--color-danger-bg);
  border-radius: var(--radius);
  padding: var(--space-3);
  margin-bottom: var(--space-3);
}
.damage-range-item { text-align: center; }
.damage-range-label { display: block; font-size: 12px; color: var(--color-text-muted); margin-bottom: 4px; }
.damage-range-value { display: block; font-weight: 700; }
.damage-range-item.min .damage-range-value { color: var(--color-warning); font-size: var(--font-size-lg); }
.damage-range-item.max .damage-range-value { color: var(--color-danger); font-size: var(--font-size-xl); }
.damage-range-arrow { font-size: var(--font-size-xl); color: var(--color-text-muted); font-weight: 300; }

.breakdown-list { display: flex; flex-direction: column; gap: var(--space-1); }
.breakdown-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--space-1) var(--space-2);
  background: var(--color-bg-subtle);
  border-radius: var(--radius-sm);
  gap: var(--space-2);
}
.breakdown-label { display: flex; align-items: center; gap: var(--space-1); font-size: var(--font-size-sm); color: var(--color-text); }
.breakdown-amount { font-weight: 600; font-size: var(--font-size-sm); color: var(--color-danger); white-space: nowrap; }
.risk-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.risk-dot.risk-low { background: var(--color-success); }
.risk-dot.risk-medium { background: var(--color-warning); }
.risk-dot.risk-high { background: var(--color-danger); }
.damage-note-small { font-size: 12px; color: var(--color-text-muted); margin-top: var(--space-2); }
```

**Step 7: 商材カード・ROI・推奨事項・事例・CTA・フッターのスタイル**

```css
/* === Product Cards === */
.product-card { border: 1px solid var(--color-border); }
.product-header { margin-bottom: var(--space-2); }
.product-badge-wrap { margin-bottom: var(--space-1); }
.product-sub { color: var(--color-text-secondary); font-size: var(--font-size-sm); margin-top: 4px; }

.urgency-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #fff;
}
.urgency-badge.urgent { background: var(--color-danger); }
.urgency-badge.recommend { background: var(--color-accent); }
.urgency-badge.reference { background: var(--color-text-muted); }

.plan-recommend { margin-bottom: var(--space-2); }
.plan-box {
  background: var(--color-bg-subtle);
  border-radius: var(--radius);
  padding: var(--space-2) var(--space-3);
}
.necfru-box { background: #f0fdf4; }
.plan-label { display: block; font-size: 12px; color: var(--color-text-secondary); margin-bottom: var(--space-1); }
.plan-name { display: block; font-size: var(--font-size-lg); font-weight: 700; color: var(--color-accent); margin-bottom: var(--space-1); }

.plan-price-row { display: flex; gap: var(--space-2); flex-wrap: wrap; }
.plan-price-item { flex: 1; min-width: 100px; }
.plan-price-label { display: block; font-size: 12px; color: var(--color-text-secondary); margin-bottom: 2px; }
.plan-price-value { display: block; font-size: var(--font-size-base); font-weight: 600; color: var(--color-text); }
.plan-price-value small { font-size: 12px; font-weight: 400; }
.plan-price-item.highlight .plan-price-value { color: var(--color-danger); font-size: var(--font-size-lg); }

.product-features { display: flex; flex-direction: column; gap: 6px; }
.feature-item {
  font-size: var(--font-size-sm);
  color: var(--color-text);
  padding: var(--space-1) var(--space-2);
  background: var(--color-bg-subtle);
  border-radius: var(--radius-sm);
}

/* === ROI === */
.roi-card { background: var(--color-bg-card); border: 1px solid var(--color-border); }
.roi-grid { display: flex; align-items: center; gap: var(--space-2); margin: var(--space-2) 0; flex-wrap: wrap; }
.roi-item {
  flex: 1;
  min-width: 160px;
  background: var(--color-bg-subtle);
  border-radius: var(--radius);
  padding: var(--space-2);
}
.roi-item.risk { border-left: 3px solid var(--color-danger); }
.roi-item.cost { border-left: 3px solid var(--color-success); }
.roi-label { display: block; font-size: 12px; color: var(--color-text-secondary); margin-bottom: 4px; }
.roi-value { display: block; font-size: var(--font-size-lg); font-weight: 700; color: var(--color-text); }
.roi-detail { display: block; font-size: 12px; color: var(--color-text-muted); margin-top: 2px; }
.roi-vs { font-size: var(--font-size-xl); font-weight: 300; color: var(--color-text-muted); }
.roi-message {
  background: var(--color-bg-subtle);
  border-radius: var(--radius);
  padding: var(--space-2);
  font-size: var(--font-size-sm);
  line-height: 1.7;
  color: var(--color-text);
}
.roi-message strong { color: var(--color-accent); }

/* === Recommendations === */
.rec-list { display: flex; flex-direction: column; gap: var(--space-2); }
.rec-item {
  padding: var(--space-2);
  border-radius: var(--radius-sm);
  border-left: 3px solid;
  background: var(--color-bg-subtle);
}
.rec-item.priority-high { border-left-color: var(--color-danger); background: var(--color-danger-bg); }
.rec-item.priority-medium { border-left-color: var(--color-warning); background: #fffbeb; }
.rec-item.priority-low { border-left-color: var(--color-success); background: #f0fdf4; }
.priority-badge {
  font-size: 12px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: var(--radius-sm);
  display: inline-block;
  margin-bottom: var(--space-1);
  color: #fff;
}
.priority-high .priority-badge { background: var(--color-danger); }
.priority-medium .priority-badge { background: var(--color-warning); }
.priority-low .priority-badge { background: var(--color-success); }
.rec-item p { font-size: var(--font-size-sm); color: var(--color-text); }

/* === Cases === */
.cases-list { display: flex; flex-direction: column; gap: var(--space-2); }
.case-item {
  background: var(--color-bg-subtle);
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  padding: var(--space-3);
}
.case-header { display: flex; align-items: flex-start; gap: var(--space-2); margin-bottom: var(--space-1); }
.case-icon { font-size: var(--font-size-xl); flex-shrink: 0; line-height: 1; }
.case-title-wrap { flex: 1; }
.case-year {
  font-size: 12px; color: #fff; background: var(--color-text-secondary);
  padding: 1px 8px; border-radius: 10px; display: inline-block; margin-bottom: 4px;
}
.case-title { font-size: var(--font-size-base); font-weight: 600; color: var(--color-text); margin: 2px 0 2px; }
.case-org { font-size: 12px; color: var(--color-text-muted); display: block; }
.case-damage-wrap { text-align: right; flex-shrink: 0; }
.case-damage-label { display: block; font-size: 12px; color: var(--color-text-muted); margin-bottom: 2px; }
.case-damage-amount { display: block; font-size: var(--font-size-base); font-weight: 700; color: var(--color-danger); white-space: nowrap; }
.case-description { font-size: var(--font-size-sm); color: var(--color-text-secondary); line-height: 1.6; margin-bottom: var(--space-1); }
.case-footer { display: flex; flex-direction: column; gap: var(--space-1); margin-bottom: var(--space-1); }
.case-lesson {
  font-size: var(--font-size-sm); color: var(--color-text);
  background: #f0fdf4; border-left: 3px solid var(--color-success);
  padding: var(--space-1) var(--space-2); border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
}
.lesson-label { font-weight: 600; color: var(--color-success); margin-right: 6px; }
.case-comparison {
  font-size: var(--font-size-sm); color: var(--color-text);
  background: #fffbeb; border-left: 3px solid var(--color-warning);
  padding: var(--space-1) var(--space-2); border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
  display: flex; gap: var(--space-1); align-items: flex-start;
}
.comparison-icon { flex-shrink: 0; }
.case-source { font-size: 12px; color: var(--color-text-muted); margin: 0; }
.cases-summary {
  margin-top: var(--space-2); padding: var(--space-2); background: var(--color-accent);
  color: #fff; border-radius: var(--radius); font-size: var(--font-size-sm); line-height: 1.6;
}
.cases-summary strong { color: #fbbf24; }

/* === CTA === */
.cta-card { text-align: center; background: var(--color-bg-subtle); border: 1px solid var(--color-border); }
.cta-card h2 { border-bottom-color: var(--color-border); }
.cta-card p { color: var(--color-text-secondary); margin-bottom: var(--space-3); }
.cta-actions { display: flex; justify-content: center; gap: var(--space-2); flex-wrap: wrap; }

/* === Footer === */
footer { text-align: center; margin-top: var(--space-4); font-size: 12px; color: var(--color-text-muted); }
```

**Step 8: レスポンシブ＆印刷CSSを記述**

```css
/* === Responsive === */
@media (max-width: 768px) {
  .container { padding: var(--space-2) var(--space-2) var(--space-6); }
  .card { padding: var(--space-3); }
  header { padding: var(--space-4) 0 var(--space-3); }
  header h1 { font-size: var(--font-size-lg); }
  .form-row { flex-direction: column; gap: var(--space-2); }
  .plan-price-row { flex-direction: column; gap: var(--space-1); }
  .roi-grid { flex-direction: column; }
  .roi-vs { display: none; }
}
@media (max-width: 640px) {
  .report-header { flex-direction: column; align-items: flex-start; }
  .score-circle { align-self: center; }
  .damage-range { flex-direction: column; gap: var(--space-2); }
  .damage-range-arrow { transform: rotate(90deg); }
  .breakdown-item { flex-direction: column; align-items: flex-start; }
  .btn-wrap { flex-direction: column; }
  .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
  .case-header { flex-direction: column; }
  .case-damage-wrap { text-align: left; }
  .radio-group { flex-direction: column; gap: var(--space-1); }
}

/* === Print === */
@media print {
  body { background: #fff; font-size: 11pt; }
  .container { max-width: 100%; padding: 0; }
  .btn-wrap, .cta-card, .cta-actions, .progress-bar, .step-indicator { display: none; }
  .card {
    box-shadow: none;
    border: 1px solid #ddd;
    page-break-inside: avoid;
    margin-bottom: 16px;
    padding: 16px;
  }
  .damage-range, .roi-grid { page-break-inside: avoid; }
  .product-card { page-break-before: auto; }
  .cases-card { page-break-before: always; }
  header { padding: 16px 0; }
  header h1 { font-size: 18pt; }
}
```

**Step 9: 動作確認**

ブラウザで各画面を確認し、CSS変数が正しく適用されているか、レイアウト崩れがないかを確認する。

**Step 10: コミット**

```bash
git add css/style.css
git commit -m "style: rewrite CSS with clean & minimal design system"
```

---

## Task 2: Google Fonts読み込み — 共通headタグ

**Files:**
- Modify: `index.php:4-5`
- Modify: `assessment.php:4-5`
- Modify: `result.php:4-5`

**Step 1: 各PHPファイルのheadにGoogle Fontsリンクを追加**

3つのPHPファイルすべてで、`<link rel="stylesheet" href="css/style.css">` の直前に以下を追加:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+JP:wght@400;500;600;700&display=swap" rel="stylesheet">
```

**Step 2: コミット**

```bash
git add index.php assessment.php result.php
git commit -m "style: add Google Fonts (Inter + Noto Sans JP)"
```

---

## Task 3: index.php改修 — プログレスバー追加＋フォームバリデーション

**Files:**
- Modify: `index.php`

**Step 1: プログレスバーのHTML追加**

`<header>` 直後、`.card` 直前に以下を追加:

```html
<div class="progress-bar">
    <div class="step active">1. 企業情報</div>
    <div class="step-line"></div>
    <div class="step">2. セキュリティ診断</div>
    <div class="step-line"></div>
    <div class="step">3. 診断結果</div>
</div>
```

**Step 2: フォームにバリデーションエラー表示用要素を追加**

各 `<input>` の直後に以下を追加:

```html
<span class="field-error" id="err_{field_name}"></span>
```

対象: `company_name`, `employees`, `pc_count`

**Step 3: インラインJSでリアルタイムバリデーションを追加**

`</body>` 直前に以下を追加:

```html
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const rules = {
        company_name: { required: true, message: '企業名を入力してください' },
        employees: { required: true, min: 1, max: 99999, message: '1〜99999の数値を入力してください' },
        pc_count: { required: true, min: 1, max: 99999, message: '1〜99999の数値を入力してください' },
    };

    function validate(field) {
        const rule = rules[field.name];
        if (!rule) return true;
        const err = document.getElementById('err_' + field.name);
        if (!err) return true;

        let valid = true;
        if (rule.required && !field.value.trim()) valid = false;
        if (rule.min !== undefined && Number(field.value) < rule.min) valid = false;
        if (rule.max !== undefined && Number(field.value) > rule.max) valid = false;

        if (!valid) {
            field.classList.add('error');
            err.textContent = rule.message;
            err.classList.add('show');
        } else {
            field.classList.remove('error');
            err.classList.remove('show');
        }
        return valid;
    }

    Object.keys(rules).forEach(function(name) {
        const field = form.querySelector('[name="' + name + '"]');
        if (field) {
            field.addEventListener('blur', function() { validate(field); });
            field.addEventListener('input', function() {
                if (field.classList.contains('error')) validate(field);
            });
        }
    });

    form.addEventListener('submit', function(e) {
        let allValid = true;
        Object.keys(rules).forEach(function(name) {
            const field = form.querySelector('[name="' + name + '"]');
            if (field && !validate(field)) allValid = false;
        });
        if (!allValid) e.preventDefault();
    });
});
</script>
```

**Step 4: 動作確認**

- 空欄で送信 → エラー表示されること
- 値入力後にエラーが消えること
- 正常入力で次画面に遷移すること

**Step 5: コミット**

```bash
git add index.php
git commit -m "feat: add progress bar and form validation to index.php"
```

---

## Task 4: assessment.php改修 — カテゴリ別ステップ分割

**Files:**
- Modify: `assessment.php`

**Step 1: プログレスバーのHTMLをドット＆ライン型に変更**

```html
<div class="progress-bar">
    <div class="step completed">1. 企業情報</div>
    <div class="step-line completed"></div>
    <div class="step active">2. セキュリティ診断</div>
    <div class="step-line"></div>
    <div class="step">3. 診断結果</div>
</div>
```

**Step 2: ミニプログレス表示を追加**

formの先頭（hiddenフィールドの直後）に以下を追加:

```html
<div class="step-indicator">
    <span class="step-indicator-label" id="catLabel"></span>
    <div class="step-indicator-bar">
        <div class="step-indicator-fill" id="catProgress" style="width: 20%"></div>
    </div>
    <span class="step-indicator-count" id="catCount">1 / 5</span>
</div>
```

**Step 3: 各check-categoryに `data-step` 属性を追加**

PHPのforeachループ内で、カテゴリのdivにインデックスを付与する:

```php
<?php $step_index = 0; ?>
<?php foreach (ASSESSMENT_CATEGORIES as $cat_id => $cat): ?>
<div class="check-category" data-step="<?= $step_index ?>">
    <!-- ...既存の内容... -->
</div>
<?php $step_index++; ?>
<?php endforeach; ?>
```

**Step 4: ボタンエリアを変更**

既存の `.btn-wrap` を以下に置き換え:

```html
<div class="btn-wrap">
    <button type="button" id="prevBtn" class="btn-secondary" style="display:none">前のカテゴリへ</button>
    <button type="button" id="nextBtn" class="btn-primary">次のカテゴリへ</button>
    <button type="submit" id="submitBtn" class="btn-primary" style="display:none">診断結果を見る</button>
</div>
```

**Step 5: ステップ切替JSを追加**

`</body>` 直前に以下を追加:

```html
<script>
document.addEventListener('DOMContentLoaded', function() {
    var categories = document.querySelectorAll('.check-category');
    var totalSteps = categories.length;
    var currentStep = 0;

    var prevBtn = document.getElementById('prevBtn');
    var nextBtn = document.getElementById('nextBtn');
    var submitBtn = document.getElementById('submitBtn');
    var catLabel = document.getElementById('catLabel');
    var catProgress = document.getElementById('catProgress');
    var catCount = document.getElementById('catCount');

    function showStep(index) {
        categories.forEach(function(cat, i) {
            cat.style.display = i === index ? 'block' : 'none';
        });
        currentStep = index;

        // ミニプログレス更新
        var pct = ((index + 1) / totalSteps * 100);
        catProgress.style.width = pct + '%';
        catCount.textContent = (index + 1) + ' / ' + totalSteps;
        var h3 = categories[index].querySelector('h3');
        catLabel.textContent = h3 ? h3.textContent.trim() : '';

        // ボタン表示制御
        prevBtn.style.display = index > 0 ? '' : 'none';
        if (index < totalSteps - 1) {
            nextBtn.style.display = '';
            submitBtn.style.display = 'none';
        } else {
            nextBtn.style.display = 'none';
            submitBtn.style.display = '';
        }

        updateNextBtnState();
    }

    function allAnswered(stepIndex) {
        var radios = categories[stepIndex].querySelectorAll('input[type="radio"]');
        var names = {};
        radios.forEach(function(r) { names[r.name] = true; });
        var nameList = Object.keys(names);
        return nameList.every(function(name) {
            return categories[stepIndex].querySelector('input[name="' + name + '"]:checked');
        });
    }

    function updateNextBtnState() {
        var answered = allAnswered(currentStep);
        nextBtn.disabled = !answered;
        submitBtn.disabled = !answered;
    }

    // ラジオ変更時にボタン状態更新 + 回答済みスタイル
    document.querySelectorAll('input[type="radio"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            updateNextBtnState();
            var item = radio.closest('.check-item');
            if (item) item.classList.add('answered');
        });
    });

    nextBtn.addEventListener('click', function() {
        if (allAnswered(currentStep) && currentStep < totalSteps - 1) {
            showStep(currentStep + 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    prevBtn.addEventListener('click', function() {
        if (currentStep > 0) {
            showStep(currentStep - 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    showStep(0);
});
</script>
```

**Step 6: 動作確認**

- カテゴリ1のみ表示されること
- 全問回答で「次のカテゴリへ」が有効になること
- 最終カテゴリで「診断結果を見る」が表示されること
- 「前のカテゴリへ」で戻れること
- ミニプログレスが更新されること

**Step 7: コミット**

```bash
git add assessment.php
git commit -m "feat: add step-by-step category navigation to assessment"
```

---

## Task 5: result.php改修 — セクション順序変更＋デザイン刷新

**Files:**
- Modify: `result.php`

**Step 1: プログレスバーをドット＆ライン型に変更**

```html
<div class="progress-bar">
    <div class="step completed">1. 企業情報</div>
    <div class="step-line completed"></div>
    <div class="step completed">2. セキュリティ診断</div>
    <div class="step-line completed"></div>
    <div class="step active">3. 診断結果</div>
</div>
```

**Step 2: 診断サマリーのHTML変更**

グラデーション背景を廃止。`.result-summary` のclass名は維持し、CSSで白背景に変更済み。
`report-meta` 内のテキストカラーをinlineスタイルから削除（CSSで制御）:

- `style="color:..."` の `risk-message` はそのまま維持（動的な色のため）
- `score-circle` の `style="border-color:...; color:..."` もそのまま維持（動的な色のため）

**Step 3: セクション順序を変更**

HTMLブロックを以下の順序に並べ替え:

1. `<!-- ① 診断サマリー -->` （維持）
2. `<!-- ② カテゴリ別レーダーチャート -->` （維持）
3. `<!-- ③ 想定被害額 -->` （維持）
4. `<!-- ⑧ 実際の被害事例 -->` ← ここに移動
5. `<!-- ④ DDHBOX提案 -->` （維持）
6. `<!-- ⑤ necfru MAM/DAM提案 -->` （維持）
7. `<!-- ⑥ 費用対効果サマリー -->` （維持）
8. `<!-- ⑦ 改善推奨事項 -->` （維持）
9. `<!-- ⑨ アクション -->` （維持）

HTMLの中身自体は変更不要。セクション単位での移動のみ。

**Step 4: ROIカードのスタイル調整**

ROIカードの `.roi-card` からinlineスタイル的な色指定をHTMLから削除不要（CSSで上書き済み）。
ただし `roi-message` 内の `strong` タグの色が `#ffcc80`（金色）→ CSSで `var(--color-accent)` に変更済み。
これにより白背景でも視認性が確保される。

**Step 5: 動作確認**

- 全セクションが正しい順序で表示されること
- レーダーチャートが描画されること
- スコアサークルの色が動的に変わること
- 印刷プレビューでページブレークが適切なこと

**Step 6: コミット**

```bash
git add result.php
git commit -m "feat: reorder result sections and update markup for new design"
```

---

## Task 6: Playwright動作確認

**Files:**
- 全画面の通し確認

**Step 1: index.phpの表示確認**

ブラウザでindex.phpを開き、以下を確認:
- Google Fontsが読み込まれている（Inter/Noto Sans JP）
- プログレスバーがドット＆ライン型で表示される
- フォームの入力フィールドが統一されたスタイル
- バリデーションが動作する

**Step 2: assessment.phpの通し確認**

index.phpからフォーム送信し、assessment.phpへ遷移:
- カテゴリ1のみ表示される
- ミニプログレスが「1/5」を示す
- 全問回答で「次のカテゴリへ」が有効になる
- 5カテゴリ完了後に「診断結果を見る」が表示される

**Step 3: result.phpの通し確認**

全問回答後に結果画面へ遷移:
- セクション順序が正しい（被害事例 → DDHBOX → necfru）
- レーダーチャートが描画される
- スコアサークルが正しい色で表示される
- 印刷プレビューが適切

**Step 4: レスポンシブ確認**

ブラウザのデベロッパーツールで以下のサイズを確認:
- 768px（iPad）
- 640px（スマートフォン）

**Step 5: 最終コミット（必要に応じて修正分）**

```bash
git add -A
git commit -m "fix: address visual issues found during testing"
```

---

## 実装順序まとめ

| Task | 内容 | 依存 |
|------|------|------|
| 1 | CSS全面リライト | なし |
| 2 | Google Fonts追加 | なし |
| 3 | index.php改修 | Task 1, 2 |
| 4 | assessment.php改修 | Task 1, 2 |
| 5 | result.php改修 | Task 1, 2 |
| 6 | Playwright動作確認 | Task 3, 4, 5 |

**Task 1と2は並列実行可能。Task 3, 4, 5も並列実行可能。**
