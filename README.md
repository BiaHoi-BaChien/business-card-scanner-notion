# business-card-scanner-notion

スマホでの名刺撮影 → AI による情報抽出 → Notion データベースへの連絡先登録ワークフローのサンプル実装

## セットアップ

1. `.env.example` をコピーして `.env` を作成し、必要なキーを設定します。
   ```env
   OPENAI_API_KEY=sk-...
   NOTION_API_KEY=secret_...
   NOTION_DATA_SOURCE_ID=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   NOTION_VERSION=2025-09-03
   ```
2. 依存関係をインストールします。
   ```bash
   pip install -r requirements.txt
   ```
3. Streamlit アプリを起動します。
   ```bash
   streamlit run app.py
   ```

## 使い方

- アプリの UI から名刺画像（表・裏の最大 2 枚）をアップロードします。
- OpenAI API が連絡先を抽出し、Notion API を通じて指定のデータソースに登録します。
- 抽出結果と Notion への登録状況が画面に表示されます。

## プロパティ名のカスタマイズ

アプリは `property_config.json` に定義されたプロパティ名を使って Notion に登録します。ファイルが無い場合やキーが欠けている場合は以下のデフォルト値を使用します。

```json
{
  "name": "名前",
  "company": "会社名",
  "website": "会社HP",
  "email": "メールアドレス",
  "phone_number_1": "電話番号1",
  "phone_number_2": "電話番号2",
  "industry": "業種"
}
```

- `会社名` は **セレクト** プロパティとして送信され、選択肢に存在しない値は API がデータベースに追加します。
- `メールアドレス` は **リッチテキスト** プロパティとして送信されます。
