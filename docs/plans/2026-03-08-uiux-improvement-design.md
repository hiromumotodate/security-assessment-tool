# UI/UX改善デザイン設計書

**作成日**: 2026-03-08
**方針**: クリーン＆ミニマル（Apple/Vercel系）
**スコープ**: フロントエンド全面改修（CSS + HTML + JS + 印刷対応）

---

## 1. デザインシステム

### 1.1 カラートークン（CSS変数）

```css
:root {
  --color-bg-primary: #fafafa;
  --color-bg-secondary: #f5f5f5;
  --color-bg-card: #ffffff;
  --color-text-primary: #171717;
  --color-text-secondary: #666666;
  --color-text-muted: #a3a3a3;
  --color-accent: #1a237e;
  --color-accent-hover: #283593;
  --color-border: #e5e5e5;
  --color-error: #ef4444;
  --color-success: #22c55e;
  --color-warning: #f59e0b;
  --color-danger: #dc2626;
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
  --shadow-md: 0 2px 8px rgba(0,0,0,0.06);
  --radius: 8px;
  --transition: 150ms ease-out;
}
```

### 1.2 タイポグラフィ

- フォント: `Inter, "Noto Sans JP", system-ui, sans-serif`（Google Fonts CDN）
- 見出し: font-weight 600, line-height 1.2
- 本文: font-weight 400, line-height 1.6
- サイズスケール: 14 / 16 / 20 / 24 / 32px

### 1.3 スペーシング（8pxグリッド）

- 全spacing: 8 / 16 / 24 / 32 / 48 / 64px のみ使用
- カード内padding: 24px（デフォルト）、モバイル16px
- カード間margin: 24px
- コンテナmax-width: 820px（維持）

---

## 2. コンポーネントパターン

### 2.1 フォーム

- ラベル: inputの上に配置
- input高さ: 40px統一
- フォーカス: 2pxアクセントカラーリング（box-shadow方式）
- バリデーション: フィールド直下にインライン表示、エラー色 `#ef4444`
- submitボタン: 右寄せ。モバイル時full-width
- 必須マーク: `*` を控えめに表示

### 2.2 インタラクション4状態

全インタラクティブ要素（button, input, select, radio, link）に適用:

| 状態 | スタイル |
|------|---------|
| Default | 通常表示 |
| Hover | 背景色の微変化 or opacity変化 |
| Focus | 2px accent ring（キーボードユーザー対応） |
| Disabled | opacity 50%, cursor: not-allowed |

transition: 150ms ease-out（hover）, 100ms（active）

### 2.3 カード

- box-shadow: `0 1px 3px rgba(0,0,0,0.08)` または 1px border
- border-radius: 8px統一
- 装飾的ボーダー（border-top色分け）は廃止 → 内部のバッジやラベルで差別化

### 2.4 プログレスバー

- 矢印型（現状）→ ドット＆ライン型に変更
- 完了ステップ: アクセント色の塗り丸
- 現在ステップ: アクセント色リング
- 未到達: グレー

---

## 3. 画面別改修内容

### 3.1 index.php（Step 1: 企業情報入力）

- プログレスバーを追加（Step 1がアクティブ）
- ヘッダー: 背景色なし、テキストのみ。余白を広げる
- フォームフィールド:
  - `input[type="number"]` にスタイル追加
  - 余白拡大（form-group margin-bottom: 24px）
  - フィールド間のform-rowギャップ: 16px → 24px
- リアルタイムバリデーション（JS）:
  - 空欄チェック
  - 数値範囲チェック（従業員数・PC台数）
  - エラーメッセージをフィールド直下にインライン表示

### 3.2 assessment.php（Step 2: チェックリスト）

- **カテゴリ別ステップ分割**（5ステップ）:
  - 1カテゴリ分の設問のみ表示
  - ミニプログレス: 「カテゴリ 1/5」＋バー
  - 全問回答で「次のカテゴリへ」ボタン活性化
  - 最終カテゴリで「診断結果を見る」ボタン表示
  - JSで表示切替（hidden fieldsは維持、form actionも維持）
- チェック状態の視覚フィードバック:
  - 回答済み設問: 左側に控えめなチェックアイコン or 背景色変化
  - 未回答: 通常表示
- radioボタンのタッチターゲット: 最小44x44px

### 3.3 result.php（Step 3: 診断結果）

**セクション順序変更**:

1. 診断サマリー（スコア＋リスクレベル）
2. カテゴリ別スコア（レーダーチャート＋バー）
3. 想定被害額
4. 実際の被害事例 ← 商材提案の前に移動
5. DDHBOX提案
6. necfru MAM/DAM提案
7. 費用対効果サマリー
8. 改善推奨事項
9. アクションボタン

**デザイン変更**:

- 診断サマリー: グラデーション背景廃止 → 白背景＋大きなスコア表示
- スコアサークル: 白背景＋太ボーダー（4px → 6px）、リスク色で色分け
- 商材カード: border-top装飾廃止 → 内部のurgency-badgeで差別化
- ROIカード: 濃紺背景廃止 → 白背景＋アクセント色テキスト
- 被害事例: 赤いborder-left → 控えめなグレー。被害額の数字で危機感を出す

### 3.4 css/style.css 全面リライト

- CSS変数でトークン管理
- 8pxグリッドに統一
- 全インタラクティブ要素にfocus-visible対応
- レスポンシブブレークポイント:
  - sm: 640px
  - md: 768px（タブレット）
  - lg: 1024px

### 3.5 印刷CSS

- `@media print` を本格対応
- ページブレーク制御（各セクション間）
- ボタン・ナビゲーション非表示
- 背景色を白に統一
- レーダーチャートの印刷対応

---

## 4. JS追加内容

| 対象ファイル | 機能 |
|------------|------|
| index.php（インライン） | フォームバリデーション（blur/input時リアルタイム） |
| assessment.php（インライン） | カテゴリステップ切替、未回答チェック、ミニプログレス更新 |
| result.php（インライン） | レーダーチャート（Chart.js維持）、バーアニメーション |

外部JSファイルは作成しない（現状の構成を維持）。

---

## 5. 変更しないもの

- PHPバックエンドロジック（calculator.php, config.php, etc.）
- フォームのPOST構造・hidden fields
- Chart.js CDN利用
- Supabase連携
- admin.php（今回のスコープ外）
