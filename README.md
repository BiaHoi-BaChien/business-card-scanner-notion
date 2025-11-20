# business-card-scanner-notion

スマホでの名刺撮影 → AI による情報抽出 → Notion データベースへの連絡先登録ワークフローのサンプル実装

## セットアップ

1. `.env.example` をコピーして `.env` を作成し、必要なキーを設定します。
   ```env
   OPENAI_API_KEY=sk-...
   NOTION_API_KEY=secret_...
   NOTION_DATABASE_ID=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
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
- OpenAI API が氏名・会社名などの連絡先を抽出し、Notion API を通じて指定データベースに登録します。
- 抽出結果と Notion への登録状況が画面に表示されます。
