# Watson NLC 

## IBM Watson NLC(Natural Language Classifier) を使った WordPress プラグイン

### このプラグインでできること

* 既存の WordPress データベース内に格納されたドキュメント本文とそのカテゴリー情報を IBM Watson に学習させる

* 学習させたエンジンに対して、新たに与えたテキストの情報はどのカテゴリーに属しているのかを IBM Watson に問い合わせる

### このプラグインを利用するための前提条件

1 [IBM Bluemix](http://bluemix.net/ "IBM Bluemix") を利用可能なアカウントを所有していること（無料版でも構いません）

2 IBM Bluemix 内に [IBM Watson](http://www.ibm.com/smarterplanet/jp/ja/ibmwatson/ "IBM Watson") の [NLC(Natural Language Classifier)](https://www.ibm.com/watson/developercloud/nl-classifier.html "NLC") サービスのインスタンスを１つ作成し、そのインスタンスに接続するためのユーザーIDおよびパスワードがわかっていること

3 既にある程度の文書量が格納され、かつそれらの文書にカテゴリーの指定が付与されている WordPress データベース

4 上記 WordPress データベースの管理画面にアクセスしてプラグインを追加できる権限

### このプラグインのインストール方法

* WordPress を導入したディレクトリの wp-content/plugins フォルダ内に watson-nlc プラグインを展開してください（wp-content/plugins/watson-nlc/watson-nlc.php ファイルが存在するようにしてください）。

    * [ここ](https://github.com/dotnsf/wordpress_watson_nlc/ "ここ")からプラグインをダウンロードした場合は WordPress のプラグイン新規追加機能を使って導入することができます。

* WordPress の管理画面のプラグインメニューから Watson NLC プラグインを有効化してください

* WordPress の管理画面メニューに "NLC設定" という項目が表示されることを確認してください

* "NLC設定" メニューを開き、IBM Bluemix 内で作成した NLC インスタンスにアクセスするための Username と Password を同画面内に入力して「変更を保存」してください（Query はこのタイミングで入力しても構いませんが、後で問い合わせテストの直前に入力しても構いません）。

### IBM Watson への学習


### IBM Watson への問い合わせ


## Copyright

* 2016 dotnsf@gmail.com(C) all rights reserved.

