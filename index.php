<?php
$target = isset($_GET['f']) ? trim($_GET['f']) : '';
if ($target === '') {
	readfile('err.html');
	exit;
}
$baseDir = __DIR__ . '/' . $target;
$defineFile = $baseDir . '/define.txt';
$resultFile = $baseDir . '/result.txt';
if (!file_exists($defineFile)) {
	readfile('err.html');
	exit;
}
$lines = file($defineFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$meta = ['title'=>'','until'=>'','author'=>''];
$questions = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === ';') continue;
    if (strpos($line,'title=')===0) { $meta['title'] = substr($line,6); continue; }
    if (strpos($line,'until=')===0) { $meta['until'] = substr($line,6); continue; }
    if (strpos($line,'author=')===0) { $meta['author'] = substr($line,7); continue; }
    $isRequired = false;
    if ($line[0]==='!') { $isRequired=true; $line=substr($line,1); }
    $parts = explode(' ',$line);
    $type=array_shift($parts);
    $title=array_shift($parts);
    $options=$parts;
    $questions[]= ['type'=>$type,'title'=>$title,'options'=>$options,'required'=>$isRequired];
}
if ($meta['until']!=='' && strtotime($meta['until'])<time()) {
	readfile('err.html');
	exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
	date_default_timezone_set('Asia/Tokyo');
	$output = 'Case '.date('Y/m/d H:i:s').' IP:'.$_SERVER['REMOTE_ADDR']."\n";
    foreach ($questions as $i=>$q) {
        $key = 'q'.$i;
        $answer = '';

        if ($q['type']==='select') {
            $answer = isset($_POST[$key]) ? $_POST[$key] : '';
            if ($q['required'] && $answer==='') exit('必須項目が未入力です。');
        }
        elseif ($q['type']==='selects') {
            $answerArr = isset($_POST[$key]) ? $_POST[$key] : [];
            if ($q['required'] && empty($answerArr)) exit('必須項目が未入力です。');
            $answer = implode(' ', $answerArr);
        }
        elseif ($q['type']==='write' || $q['type']==='writes') {
            $answer = isset($_POST[$key]) ? $_POST[$key] : '';
            if ($q['required'] && trim($answer)==='') exit('必須項目が未入力です。');
        }

        $output .= 'Question'.($i+1).' 「'.$q['title'].'」';
        if ($q['type']==='writes') $output .= "->\n$answer\n";
        else $output .= ' '.$answer."\n";
    }
    $output .= "[END]\n\n";
    $fp = fopen($resultFile,'a');
    if ($fp) {
        flock($fp, LOCK_EX);
        fwrite($fp, $output);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
	header('Location: thankyou.html');
	exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($meta['title'],ENT_QUOTES,'UTF-8'); ?></title>
</head>
<style>
body{font-family:"Segoe UI","Yu Gothic","Helvetica Neue",sans-serif;background-color:#e6e6fa;margin:0;padding:30px 10px}h1{font-weight:400;color:#323130;text-align:center;margin-bottom:20px}p{font-weight:400;color:#323130;text-align:center;margin-bottom:20px}p.author{text-align:center;color:#605e5c;margin-bottom:40px}form{background:#f8f8ff;padding:25px 30px;border-radius:8px;max-width:600px;margin:0 auto;box-shadow:0 1px 3px rgba(0,0,0,.1);border:1px solid #edebe9}div.question{margin-bottom:25px;padding-bottom:15px;border-bottom:1px solid #e1dfdd}label{display:block;margin-bottom:8px;font-weight:500;color:#323130}input[type="text"],textarea{width:100%;padding:10px 12px;font-size:14px;border:1px solid #c8c6c4;border-radius:4px;box-sizing:border-box;background-color:#fff}input[type="radio"],input[type="checkbox"]{margin-right:8px}button{background-color:#0078d4;color:#fff;border:none;padding:12px 24px;font-size:15px;border-radius:4px;cursor:pointer;transition:background-color 0.2s ease}button:hover{background-color:#005a9e}strong{color:#d13438}
</style>
<body>
<h1><?php echo htmlspecialchars($meta['title'],ENT_QUOTES,'UTF-8'); ?></h1>
<p>作成者：<?php echo htmlspecialchars($meta['author'],ENT_QUOTES,'UTF-8'); ?></p>
<form method="post" action="">
<?php foreach($questions as $i=>$q): ?>
<div class="question">
    <label><?php if($q['required']) echo '<strong>[*必須]</strong> '; ?>
    <?php echo htmlspecialchars($q['title'],ENT_QUOTES,'UTF-8'); ?></label><br>
    <?php if($q['type']==='select'): ?>
        <?php foreach($q['options'] as $opt): ?>
        <label><input type="radio" name="q<?php echo $i;?>" value="<?php echo htmlspecialchars($opt,ENT_QUOTES,'UTF-8');?>" <?php echo $q['required']?'required':''; ?>><?php echo htmlspecialchars($opt,ENT_QUOTES,'UTF-8');?></label>
        <?php endforeach; ?>
    <?php elseif($q['type']==='selects'): ?>
        <?php foreach($q['options'] as $opt): ?>
        <label><input type="checkbox" name="q<?php echo $i;?>[]" value="<?php echo htmlspecialchars($opt,ENT_QUOTES,'UTF-8');?>"><?php echo htmlspecialchars($opt,ENT_QUOTES,'UTF-8');?></label>
        <?php endforeach; ?>
    <?php elseif($q['type']==='write'): ?>
        <input type="text" name="q<?php echo $i;?>" <?php echo $q['required']?'required':''; ?>><br>
    <?php elseif($q['type']==='writes'): ?>
        <textarea name="q<?php echo $i;?>" rows="4" cols="40" <?php echo $q['required']?'required':''; ?>></textarea><br>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<button type="submit">送信する</button>
<br><br>
<small>formpage ver1.0 by ABATBeliever. Under MIT License.</small>
</form>

</body>
</html>
