<?php
require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/cls/Database.php";
use Dzg\Database;

// je nachdem über welchen Weg die Daten kommen, json_Array vs POST
$data = json_decode(file_get_contents('php://input'));
$_POST = $_POST ?: $data;

if (isset($_POST['id']) && isset($_POST['prn'])) {

    $id = (int)$_POST['id'];
    $prn = (int)$_POST['prn'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $userid = $_SESSION['userid'];

    $stmt = "UPDATE dzg_file SET print=:prn, chg_ip=:ip, chg_by=:by WHERE id=:id";
    $data = [
        ':prn' => $prn,
        ':ip'  => $ip,
        ':by'  => $userid,
        ':id'  => $id ];
    Database::sendSQL($stmt, $data);
}



function in($value) {
    return htmlentities($value, ENT_QUOTES, 'utf-8');
}
$name = $_POST['name'] ?? 'Unbekannt';
#var_dump($data, in($name), $_GET, $_POST);





/*

<script>
function prn_toogle(ID, PRN) {
    $.ajax({
        type: 'POST',
        url: '/assets/tools/printoption.php',
        data: {id: ID, prn: PRN}
    })
}
</script>



<form action="ziel.php" id="meinFormular">
<input type="checkbox" name="checkbox1" onclick="document.getElementById('meinFormular').submit(); ">
</form>


document.querySelector('print').addEventListener('click', async () => {
const response = await fetch('/assets/tools/printoption.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Headers': '*'
    },
    body: JSON.stringify({id: ID, prn: PRN})
})
.then(response => response.text())
.then(data => {
    jQuery('.prn').html(data);
});
})


<button onclick="myFunction()">Click me</button>
<p id="demo"></p>
<script>
function myFunction() {
  document.getElementById("demo").innerHTML = "Hello World";
}
</script>


<button id="myBtn">Try it</button>
<p id="demo"></p>
<script>
document.getElementById("myBtn").addEventListener("click", displayDate);
function displayDate() {
  document.getElementById("demo").innerHTML = Date();
}
</script>


function loadDoc() {
  const xhttp = new XMLHttpRequest();
  xhttp.onload = function() {
    document.getElementById("demo").innerHTML = this.responseText;
    }
  xhttp.open("GET", "ajax_info.txt", true);
  xhttp.send();
}

<script>
<?php
echo 'let p_el = document.querySelectorAll("p")['.$last_pal.']';
?>
let red = Math.round(p_el.getBoundingClientRect().top)%256;
let green = Math.round(p_el.getBoundingClientRect().right)%256;
p_el.style.color =  "rgb(" + red + ", " + green + ", 0)";
</script>

<?php
$php_variable = 4;
?>
<script>
var js_variable = <?php echo $php_variable; ?>;
// Test:
alert(js_variable);
</script>

<script>
    var js_variable = 4;
    window.location.href = "test.php?js_variable=" + js_variable;
</script>


function prn_toogle(ID, PRN) {
  const xhttp = new XMLHttpRequest();
  var data_str = 'id=' + ID + '&prn=' + PRN;
  xhttp.onload = function() {}
  xhttp.open('POST', \"/assets/tools/printoption.php);
  xhttp.setRequestHeader(\"Content-type\", \"application/x-www-form-urlencoded\");
  // xhttp.setRequestHeader(\"Content-type\", \"multipart/form-data\");
  xhttp.send(data_str);
}


TODO
mit JAVA, AJAX $_POST['print'] 1/0 an PHP senden, dann in DB eintragen

$id = (int)$_GET['id'];
$prn = (int)$_GET['prn'];
$ip = $_SERVER['REMOTE_ADDR'];
$stmt = "UPDATE dzg_file SET print=?, chg_ip=? WHERE id=?";
Database::fetchDB($stmt, [$prn,$ip,$id]);

<script>
function prn_toogle(id, prn) {
    window.location.href = \"/assets/tools/printoption.php?id=\" + id + \"&prn=\" + prn;
}
</script>

<script>
function prn_toogle(ID, PRN) {
  const xhttp = new XMLHttpRequest();
  xhttp.onload = function() {}
  xhttp.open('GET', \"/assets/tools/printoption.php?id=\" + ID + \"&prn=\" + PRN, true);
  xhttp.send();
}
</script>


function prn_toogle() {
  const xhttp = new XMLHttpRequest();
  xhttp.onload = function() {
    document.getElementById("demo").innerHTML = this.responseText;
    }
  xhttp.open('GET', '/assets/tools/printoption.php?id=ID&prn=PRN', true);
  xhttp.send();
}


<script>
$.ajax({
  method: \"POST\",
  url: \"/assets/tools/printoption.php\",
  data: {id: ID, prn: PRN}
})
  .done(function( response ) {
    $(\".prn\").html(response);
  });
</script>


fetch('wrap.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
    },
    body: "text=" + document.querySelector("p.unbroken").innerText
  })
  .then(response => response.text())
  .then(data => document.querySelector("p.broken").innerHTML = data);

*/
