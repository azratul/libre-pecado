<?php

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

date_default_timezone_set("America/Santiago");

$date = $data['queryResult']['parameters']['date-time'];
$meal = $data['queryResult']['parameters']['comidas'];

$message = '';

if (empty($data['queryResult']['parameters']['date-time'])) {
  $message = 'Es necesario que me digas la fecha de la que quieres saber el menú';
}
else {
  $date = date('Y/m/d', strtotime($date));

  $db_host      = getenv('DB_HOST');
  $db_schema    = getenv('DB_SCHEMA');
  $db_username  = getenv('DB_USER');
  $db_pass      = getenv('DB_PASS');

  $conn = mysqli_connect($db_host, $db_username, $db_pass, $db_schema);

  if (!$conn) {
    $message  = 'Error de conexión a base de datos';
  }
  else {
    $meal_id    = null;

    // Get meal id
    if ($meal != '') {
      $result = mysqli_query($conn, "SELECT meals_id FROM meals WHERE meals_name = '".$meal."' LIMIT 1");
      $row    = mysqli_fetch_assoc($result);

      if ($row) {
        $query_meal = ' AND meals.meals_id = '.$row['meals_id'];
      }
    }

    $sql = "SELECT menu_date, menu_description, type_name, meals_name FROM menu INNER JOIN meals ON menu.meals_id = meals.meals_id INNER JOIN type ON menu.type_id = type.type_id WHERE menu_deleted = 0 AND menu_date = '".$date."'";

    if ($row['meals_id'] != null) {
      $sql .= $query_meal;
    }

    if ($result = mysqli_query($conn, $sql)) {
      if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
          $message.= 'Para '.$row['meals_name'].' en el menú '.$row['type_name'].' hay '.$row['menu_description'];
        }
      }
      else {
        $message = 'No se han encontrado resultados para tu solicitud';
      }

      mysqli_free_result($result);
    }
    else {
      $message = 'Ha ocurrido un error al procesar tu solicitud.';
    }

    mysqli_close($conn);
  }
}

echo '{
  "payload": {
    "google": {
      "expectUserResponse": true,
      "richResponse": {
        "items": [
          {
              "simpleResponse": {
              "textToSpeech": "'.$message.'"
            }
          }
        ]
      }
    }
  }
}'

?>


