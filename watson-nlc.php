<?php
/*
Plugin Name: Watson NLC
Plugin URI: https://github.com/dotnsf/wordpress_watson_nlc
Description: IBM Watson の NLC(Natural Language Classifier)を使ったカテゴリ分類プラグイン
Author: K.Kimura
Version: 0.5
Author URI: http://twitter.com/dotnsf

Copyright 2016 K.Kimura (email : dotnsf@gmail.com)
 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
     published by the Free Software Foundation.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software.

    K.Kimura
*/



/*
//. 編集画面内で実行する部分
function check_category(){ ?>
<script type="text/javascript" src="//code.jquery.com/jquery-2.0.3.min.js"></script>
<script type="text/javascript">
function watsonNLCCheckCategory(){
  console.log( 'watsonNLCCheckCategory()' );
  //var body = $('#wp-content-editor-container').html();
  var body = $('#postdivrich').html();
  console.log( body );
  
  if( body ){
    $.ajax({
      type: "GET",
      url: ajaxurl,
      data: { 'action': 'classify_body', 'body':body },
      success: function( response ){
        console.log( response );
      },
      error: function(){
        console.log( "error" );
      }
    });
  }
  return false;
}
</script>
<input type="button" value="ボタンを追加する方法は分かった" onClick="watsonNLCCheckCategory();"/>


<?php }
add_action( 'edit_form_after_editor', check_category );
*/


//. 管理画面内で有効なプラグイン
function add_my_ajaxurl(){
?>
<script>
var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
</script>

<?php
}
add_action( 'wp_head', 'add_my_ajaxurl', 1 );


function view_debug(){
  echo get_bloginfo( 'name' );
  die();
}

function get_nlc_info(){
  $opt = get_option('nlc_options');
  $nlc_username = isset($opt['username']) ? $opt['username'] : null;
  $nlc_password = isset($opt['password']) ? $opt['password'] : null;
  $nlc_query = isset($opt['query']) ? $opt['query'] : null;

  return array($nlc_username,$nlc_password,$nlc_query);
}

function get_classifier_ids(){
  $classifier_ids = array();

  $nlc_info = get_nlc_info();
  $nlc_username = $nlc_info[0];
  $nlc_password = $nlc_info[1];

  $url = "https://" . $nlc_username . ":" . $nlc_password . "@gateway.watsonplatform.net/natural-language-classifier/api/v1/classifiers";
  $r = file_get_contents( $url );
  $json = json_decode( $r );
  $classifiers = $json->classifiers;
  if( count($classifiers) > 0 ){
    for( $i = 0; $i < count($classifiers); $i ++ ){
      $classifier = $classifiers[$i];
      $classifier_ids[] = $classifier->classifier_id;
    }
  }

  return $classifier_ids;
}

function get_classifier_id($idx=0){
  $classifier_id = '';

  $classifier_ids = get_classifier_ids();
  if( count($classifier_ids) > $idx ){
    $classifier_id = $classifier_ids[$idx];
  }

  return $classifier_id;
}

function reset_classifier_ids(){
  $r = -1;
  $classifier_ids = get_classifier_ids();
  $r = count($classifier_ids);
  if( $r > 0 ){
    $nlc_info = get_nlc_info();
    $nlc_username = $nlc_info[0];
    $nlc_password = $nlc_info[1];

    $context = array( "http" => array( "method" => "DELETE" ) );

    for( $i = 0; $i < $r; $i ++ ){
      $classifier_id = $classifier_ids[$i];
      $url = "https://" . $nlc_username . ":" . $nlc_password . "@gateway.watsonplatform.net/natural-language-classifier/api/v1/classifiers/" . $classifier_id;
      $tmp = file_get_contents( $url, false, stream_context_create( $context ) );
    }
  }

  return $r;
}

function check_classifier($classifier_id){
  $status = "Nothing";

  if( $classifier_id ){
    try{
      $nlc_info = get_nlc_info();
      $nlc_username = $nlc_info[0];
      $nlc_password = $nlc_info[1];
      $url = "https://" . $nlc_username . ":" . $nlc_password . "@gateway.watsonplatform.net/natural-language-classifier/api/v1/classifiers/" . $classifier_id;
      $r = file_get_contents( $url );
      $json = json_decode( $r );
      $status = $json->status;
    }catch( Exception $e ){
      $status = "Exception " . $e;
    }
  }

  return $status;
}

function classify_query($text){
  $r = "";
  $nlc_info = get_nlc_info();
  $nlc_username = $nlc_info[0];
  $nlc_password = $nlc_info[1];
  $classifier_id = get_classifier_id();

  $status = check_classifier( $classifier_id );
  if( $status == "Available" ){
    $url = "https://" . $nlc_username . ":" . $nlc_password . "@gateway.watsonplatform.net/natural-language-classifier/api/v1/classifiers/" . $classifier_id . "/classify?text=" . urlencode($text);
    $r = file_get_contents( $url );
    $json = json_decode( $r );

    $r = isset($json->classes) ? $json->classes : "No data";
  }else{
    $r = $status;
  }

  return $r;
}

function httpPost($url, $params, $files){
  $boundary = '------------------------------'.time();
  $contentType = 'Content-Type: multipart/form-data; boundary=' . $boundary;
  $data = '';

  $up = '';
  if( $params != null ){
    foreach( $params as $key => $value ){
      $up = $key . ":" . $value;
    }
  }

  for( $i = 0; $i < count($files); $i ++ ){
    $file = $files[$i];

    $data .= '--' . $boundary . "\r\n";
    $data .= sprintf( 'Content-Disposition: form-data; name="%s"; filename="%s"%s', $file[2], $file[0], "\r\n" );
    $data .= 'Content-Type: application/octet-stream' . "\r\n\r\n";
    $data .= $file[1] . "\r\n";
  }
  $data .= '--' . $boundary . '--' . "\r\n";

  $headers = array( $contentType, 'Content-Length: ' . strlen( $data ), 'Authorization: Basic ' . base64_encode($up) );
  $options = array( 'http' => array( 'method'=>'POST', 'content'=>$data, 'header'=>implode( "\r\n", $headers ), 'ignore_errors'=>true ) );

  try{
    $contents = file_get_contents( $url, false, stream_context_create( $options ) );
  }catch( Exception $e ){
    $contents = "Exception " . $e;
  }

  return $contents;
}

function classifier_id($idx=0){
  $classifier_id = get_classifier_id($idx);

  echo $classifier_id;
  die();
}

function train_data(){
  $r = 'ok';
  reset_classifier_ids();

  try{
    $dsn = 'mysql:dbname='.DB_NAME.';host='.DB_HOST.';charset=utf8';
    $dbh = new PDO( $dsn, DB_USER, DB_PASSWORD, array( PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8' ) );
    if( $dbh != null ){
      global $wpdb;
      $table_prefix = $wpdb->prefix; //'wp_';
      $sql = "select " . $table_prefix . "posts.post_content as content, " . $table_prefix . "posts.post_title as title, " . $table_prefix . "terms.name as category from " . $table_prefix . "posts, " . $table_prefix . "terms, " . $table_prefix . "term_relationships where " . $table_prefix . "posts.post_type = 'post' and ( " . $table_prefix . "posts.post_status = 'publish' or " . $table_prefix . "posts.post_status = 'draft' ) and " . $table_prefix . "posts.ID = " . $table_prefix . "term_relationships.object_id and " . $table_prefix . "term_relationships.term_taxonomy_id = " . $table_prefix . "terms.term_id";
      $stmt = $dbh->prepare( $sql );
      $stmt->execute();
      $lines = "";
      while( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){
        $content = $row['content'];
        $title = $row['title'];
        $category = $row['category'];

        $body = $content . $title;
        $body = str_replace( "\n", "", $body );
        $body = str_replace( "\r", "", $body );
        $body = str_replace( ",", "", $body );

        $line = $body . "," . $category . "\n";
        $lines .= $line;
      }

      $nlc_info = get_nlc_info();
      $nlc_username = $nlc_info[0];
      $nlc_password = $nlc_info[1];
      $url = "https://" . $nlc_username . ":" . $nlc_password . "@gateway.watsonplatform.net/natural-language-classifier/api/v1/classifiers";

      $params = array($nlc_username=>$nlc_password);
      $lang = isset($wp_arr['WPLANG']) ? $wp_arr['WPLANG'] : "ja";

      $file1 = array();
      $file2 = array();
      $file1[0] = "metadata.json";
      $file1[1] = '{"language":"'.$lang.'","name":"WP with Watson NLC"}';
      $file1[2] = "training_metadata";
      $file2[0] = "data.csv";
      $file2[1] = $lines;
      $file2[2] = "training_data";
      $files = array( $file1, $file2 );

      $r = httpPost( $url, $params, $files );
    }
    $dbh = null;
  }catch( Exception $e ){
    $r = "Exception: " . $e;
  }

  echo $r;
  die();
}

function classify(){
  $r = 'ok';
  try{
    $nlc_info = get_nlc_info();
    $nlc_query = $nlc_info[2];
    $r = classify_query($nlc_query);
  }catch( Exception $e ){
    $r = "Exception: " . $e;
  }

  echo json_encode($r);
  die();
}

function classify_body(){
  $r = 'ok';
  try{
    $body = $_GET['body'];
    $r = classify_query($body);
  }catch( Exception $e ){
    $r = "Exception: " . $e;
  }

  echo json_encode($r);
  die();
}
add_action( 'wp_ajax_view_debug', 'view_debug' ); //. for Login user
//add_action( 'wp_ajax_nopriv_view_debug', 'view_debug' ); //. for non-Login user
add_action( 'wp_ajax_classifier_id', 'classifier_id' ); //. for Login user
//add_action( 'wp_ajax_nopriv_classifier_id', 'classifier_id' ); //. for non-Login user
add_action( 'wp_ajax_train_data', 'train_data' ); //. for Login user
//add_action( 'wp_ajax_nopriv_train_data', 'train_data' ); //. for non-Login user
add_action( 'wp_ajax_classify', 'classify' ); //. for Login user
//add_action( 'wp_ajax_nopriv_classify', 'classify' ); //. for non-Login user
add_action( 'wp_ajax_classify_body', 'classify_body' ); //. for Login user
//add_action( 'wp_ajax_nopriv_classify_body', 'classify_body' ); //. for non-Login user


$watson_nlc = new WatsonNLC;

class WatsonNLC{
  public $hello = 'Hello';

  public function __construct(){
    add_action( 'admin_menu', array($this, 'add_pages') );
  }

  function add_pages(){
    add_menu_page( 'NLC設定', 'NLC設定', 'level_8', __FILE__, array( $this, 'nlc_option_page'), '', 26.3141592653 );
  }

  function nlc_option_page(){
    //. オプション画面に表示する内容

    //. $_POST['nlc_options[***]']) があったら保存
    if( isset( $_POST['nlc_options']) ){
      $opt = $_POST['nlc_options'];
      update_option( 'nlc_options', $opt );
      ?><div class="updated fade"><p><strong><?php _e('Options saved.'); ?></strong></p></div><?php
    }
    ?>

    <div class="wrap">
    <div id="icon-options-general" class="icon32"><br/></div><h2>NLC設定</h2>
      <form action="" method="post">
        <?php
        wp_nonce_field('nlcoptions');
        $opt = get_option('nlc_options');
        $nlc_username = isset($opt['username']) ? $opt['username'] : null;
        $nlc_password = isset($opt['password']) ? $opt['password'] : null;
        $nlc_query = isset($opt['query']) ? $opt['query'] : null;
        ?>
        <table class="form-table">
          <tr valign="top">
            <th scope="row"><label for="username">Username</label></th>
            <td><input name="nlc_options[username]" type="text" id="username" value="<?php echo $nlc_username; ?>" class="regular-text" /></td>
          </tr>
          <tr valign="top">
            <th scope="row"><label for="password">Password</label></th>
            <td><input name="nlc_options[password]" type="password" id="password" value="<?php echo $nlc_password; ?>" class="regular-text" /></td>
          </tr>
          <tr valign="top">
            <th scope="row"><label for="query">Query</label></th>
            <td><input name="nlc_options[query]" type="text" id="nlc_text" value="<?php echo $nlc_query; ?>" class="regular-text" /><br/>
            <div id="output"></div></td>
          </tr>
        </table>
        <p class="submit"><input type="submit" name="Submit" class="button-primary" value="変更を保存" /></p>
      </form>
    <!-- /.wrap --></div>

    <script type="text/javascript" src="//code.jquery.com/jquery-2.0.3.min.js"></script>
    <script type="text/javascript">
    function nlcTrain(){
      console.log( "nlcTrain()" );
      if( window.confirm( 'IBM Watson の学習データを更新します（しばらく時間がかかります）。よろしいですか？' ) ){
        $('#output').html( '' );
        $.ajax({
          type: "GET",
          url: ajaxurl,
          data: { 'action': 'train_data' },
          success: function( response ){
            console.log( response );
            $('#output').html( 'データは学習中です。' );
          },
          error: function(){
            console.log( "error" );
          }
        });
        return false;
      }
    }

    function nlcClassify(){
      var body = $('#nlc_text').val();
      console.log( "nlcClassify(): " + body );
      $.ajax({
        type: "GET",
        url: ajaxurl,
        data: { 'action': 'classify_body', 'body':body },
        success: function( response ){
          $('#output').html( '' );
          if( typeof response === "string" ){
            $('#output').html( response );
          }else{
            var tbl = "<table border='1'><tr><th>#</th><th>category</th><th>confidence</th></tr>";
            var classes = $.parseJSON( response );
            for( i = 0; i < classes.length; i ++ ){
              var cls = classes[i];
              var cls_name = cls['class_name'];
              var cls_confidence = cls['confidence'];
              tbl += "<tr><td>" + (i+1) + "</td><td>" + cls_name + "</td><td>" + cls_confidence + "</td></tr>";
            }
            tbl += "</table>";
            $('#output').html( tbl );
          }
        },
        error: function(){
          console.log( "error" );
        }
      });
      return false;
    }

    function nlcDebug(){
      console.log( "nlcDebug()" );
      $.ajax({
        type: "GET",
        url: ajaxurl,
        data: { 'action': 'view_debug' },
        success: function( response ){
          console.log( response );
        },
        error: function(){
          console.log( "error" );
        }
      });
      return false;
    }
    </script>

    <hr/>
    <div class="wrap">
<!--
      <input type="button" name="nlc_debug" id="nlc_debug" value="Debug" onClick="nlcDebug();" />
-->
      <input type="button" name="nlc_train" id="nlc_train" value="学習" onClick="nlcTrain();" />
      <input type="button" name="nlc_classify" id="nlc_classify" value="問い合わせ" onClick="nlcClassify();" />
    <!-- /.wrap --></div>


    <?php
  }

  function get_username(){
    $opt = get_option( 'nlc_options' );
    return isset( $opt['username'] ) ? $opt['username'] : null;
  }

  function get_password(){
    $opt = get_option( 'nlc_options' );
    return isset( $opt['password'] ) ? $opt['password'] : null;
  }

  function get_query(){
    $opt = get_option( 'nlc_options' );
    return isset( $opt['query'] ) ? $opt['query'] : null;
  }


  public function get_classifier_id(){
    $username = get_username();
    $password = get_password();
    $url = 'https://' . $username . ':' . $password . '@gateway.watsonplatform.net/natural-language-classifier/api/v1/classifiers';
/*
    $r = file_get_contents( $url );
    $json = json_decode( $r );
    $classifiers = $json['classifiers'];

    return ( $classifiers && isset( $classifiers[0] ) ? $classifiers[0] : null );
*/
    return $url;
  }

  public function train_nlc(){
  }

  public function ask_nlc($text){
    $url = 'https://gateway.watsonplatform.net/natural-language-classifier/api/v1/';
  }
}



?>

