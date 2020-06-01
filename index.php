<?php
function checkMenu($data){
  if (!empty($data['queryResult']['parameters']['date-time'])) {
    $date = $data['queryResult']['parameters']['date-time'];
    $meal = $data['queryResult']['parameters']['comidas'];
    $date = date('Y/m/d', strtotime($date));

    $db_host     = getenv('DB_HOST');
    $db_schema   = getenv('DB_SCHEMA');
    $db_username = getenv('DB_USER');
    $db_pass     = getenv('DB_PASS');

    $conn = mysqli_connect($db_host, $db_username, $db_pass, $db_schema);

    if ($conn) {
      $message = 'No se han encontrado resultados para tu solicitud';
      // Get meal id
      if ($meal != '') {
        $result = mysqli_query($conn, "SELECT meals_id FROM meals WHERE meals_name = '".$meal."' LIMIT 1");
        $row    = mysqli_fetch_assoc($result);

        if ($row) {
          $query_meal = ' AND meals.meals_id = '.$row['meals_id'];
        }
      }

      $sql = "SELECT menu_description, type_name, meals_name ";
      $sql.= "FROM menu INNER JOIN meals ON menu.meals_id = meals.meals_id ";
      $sql.= "INNER JOIN type ON menu.type_id = type.type_id ";
      $sql.= "WHERE menu_deleted = 0 AND menu_date = '".$date."'";

      if ($row['meals_id'] != null) {
        $sql .= $query_meal;
      }

      if ($result = mysqli_query($conn, $sql)) {
        if (mysqli_num_rows($result) > 0) {
          $message = '';
          while ($row = mysqli_fetch_assoc($result)) {
            $message.= 'Para '.$row['meals_name'].' en el menú '.$row['type_name'].' hay '.utf8_encode($row['menu_description']).' ';
          }
        }

        mysqli_free_result($result);
      }

      mysqli_close($conn);
      return $message;
    }
    
    return 'Ha ocurrido un error al procesar tu solicitud.';
  }

  return 'Es necesario que me digas la fecha de la que quieres saber el menú';
}

header('Content-Type: application/json');
date_default_timezone_set("America/Santiago");
$response = 'No se aceptan estos requests';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $response = checkMenu(json_decode(file_get_contents('php://input'), true));
}

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