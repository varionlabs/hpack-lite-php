# minihpack-php 開発計画

## 1. 目的
- `spec.md` の要件に沿って、PHP 8.2+ で動作する最小 HPACK ライブラリを実装する。
- 対象は Static Table ベースの decode/encode のみとし、Dynamic Table / Huffman は未対応として明示的に例外化する。
- Composer で利用可能なライブラリ構成と、再現可能なテスト基盤を用意する。

## 2. スコープ
- 対象
  - HPACK Integer Representation（prefix 5/6/7）
  - String Literal（Huffman=0 のみ）
  - Static Table（1-indexed）
  - Header Field Representation
    - Indexed Header Field
    - Literal Header Field without Indexing
    - Literal Header Field never Indexed
- 非対象
  - Dynamic Table 全般
  - Dynamic Table Size Update
  - Huffman encode/decode
  - HTTP/2 フレーム処理

## 3. 成果物
- 実装
  - `src/Hpack/Decoder.php`
  - `src/Hpack/Encoder.php`
  - `src/Hpack/StaticTable.php`
  - `src/Hpack/Integer.php`
  - `src/Hpack/StringLiteral.php`
  - `src/Hpack/Exceptions.php`
- テスト
  - `tests/` 配下（PHPUnit）
- ドキュメント
  - `README.md`（使い方・未対応機能・例外方針）
  - この開発計画

## 4. 実装方針
1. `declare(strict_types=1);` を全ファイルで有効化
2. すべての処理をバイナリ安全に実装（`strlen`/`ord`/`substr`）
3. 例外は offset と種別が分かるメッセージを持たせる
4. Dynamic/Huffman は `Unsupported` 系例外で早期失敗

## 5. API 設計（公開）
- `Hpack\Decoder::decode(string $headerBlock): array`
  - 戻り値: `[['name' => string, 'value' => string], ...]`
- `Hpack\Encoder::encode(array $headers): string`
  - 入力は以下を受け付ける
    - `[['name' => '...', 'value' => '...'], ...]`
    - `['name' => 'value']`
  - エンコード優先順位
    1. static table の name/value 完全一致 → Indexed
    2. name のみ一致 → Literal without indexing（indexed name）
    3. 一致なし → Literal without indexing（new name）

## 6. 開発フェーズ

### Phase 1: 基盤整備
- Composer 設定（autoload, test script）
- 名前空間とディレクトリ雛形作成
- 例外クラス定義（`DecodeException`, `EncodeException`, `UnsupportedFeatureException` など）

### Phase 2: 低レベル部品
- `Integer` 実装
  - `decodeInteger(string $data, int $offset, int $prefixBits): array`
  - `encodeInteger(int $value, int $prefixBits, int $prefixMaskBase): string`
  - 境界値と入力破損の検証
- `StringLiteral` 実装
  - Huffman=1 検出時に `Unsupported`
  - 長さ不足時の例外

### Phase 3: Static Table
- RFC 準拠の static table を 1-indexed で定義
- `getByIndex`, `findIndexByPair`, `findIndexByName` の提供
- index=0・範囲外の扱いを明確化

### Phase 4: Decoder
- 表現種別ごとの分岐実装
- 未対応種別（with indexing, dynamic table update）で `Unsupported`
- オフセット追跡つきで連続デコード

### Phase 5: Encoder
- ヘッダー正規化（2形式入力の統一）
- static table 検索ロジック実装
- Literal without indexing の生成実装

### Phase 6: テスト
- `spec.md` 必須ケースを網羅
  - Integer prefix=5/6/7 の境界
  - String literal（空/ASCII/バイナリ/不足）
  - Indexed decode（`:method: GET` など）
  - Literal without indexing（indexed name / new name）の decode/encode
  - Huffman=1 → `Unsupported`
  - Dynamic table update → `Unsupported`
  - index=0 → 例外
  - 途中切れバッファ → 例外
- 追加推奨
  - round-trip（encode→decode）テスト
  - 異常系メッセージに offset が含まれることの確認

### Phase 7: 仕上げ
- README に制限事項と実例を記載
- `composer test` で一括実行
- 公開リポジトリへ push

## 7. 完了条件（Definition of Done）
- `spec.md` の必須要件が実装済み
- 必須テストがすべて成功
- 未対応機能は明示的に `Unsupported` で失敗する
- Composer 経由でインストール・autoload が機能する
- 公開リポジトリ `https://github.com/varionlabs/minihpack-php` で再現可能

