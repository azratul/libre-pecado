<?php
function checkMenu($data){
  $db_host     = getenv('DB_HOST');
  $db_schema   = getenv('DB_SCHEMA');
  $db_username = getenv('DB_USER');
  $db_pass     = getenv('DB_PASS');

  $conn = mysqli_connect($db_host, $db_username, $db_pass, $db_schema);

  $sql = "SELECT menu_description as menu_description, type_name, meals_name ";
  $sql.= "FROM menu INNER JOIN meals ON menu.meals_id = meals.meals_id ";
  $sql.= "INNER JOIN type ON menu.type_id = type.type_id ";
  $sql.= "WHERE menu_deleted = 0 AND menu_date = '6/1/2020' ";
  $sql.= "AND meals.meals_id = 3";

  echo $sql;

  if ($result = mysqli_query($conn, $sql)) {
    if (mysqli_num_rows($result) > 0) {
      $message = '';
      while ($row = mysqli_fetch_assoc($result)) {
        $message.= 'Para '.$row['meals_name'].' en el menú '.$row['type_name'].' hay '.$row['menu_description'];
      }
      return $message;
    }

    mysqli_free_result($result);
  }

  mysqli_close($conn);
 
  return 'Ha ocurrido un error al procesar tu solicitud.';
}

header('Content-Type: application/json');
date_default_timezone_set("America/Santiago");
$response = checkMenu(json_decode(file_get_contents('php://input'), true));

echo '{
  "payload": {
    "google": {
      "expectUserResponse": false,
      "richResponse": {
        "items": [
          {
            "simpleResponse": {
              "textToSpeech": "'.$response.'"
            }
          }
        ]
      }
    }
  }
}'
?>