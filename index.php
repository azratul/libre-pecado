<?php

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

date_default_timezone_set("America/Santiago");

$date = $data['queryResult']['parameters']['date-time'];
$meal = $data['queryResult']['parameters']['comidas'];

if (empty($data['queryResult']['parameters']['date-time'])) {
  $message = 'Es necesario que me digas la fecha de la que quieres saber el menú';
}
else {
  $db_host      = getenv('DB_HOST');
  $db_schema    = getenv('DB_SCHEMA');
  $db_username  = getenv('DB_USER');
  $db_pass      = getenv('DB_PASS');

  $message      = '';

  $conn = mysqli_connect($db_host, $db_username, $db_pass, $db_schema);

  if (!$conn) {
    $message  = 'Error de conexión a base de datos';
  }
  else {
    $meal_id    = null;

    // Get meal id
    if ($meal != '') {
      $result = mysqli_query("SELECT meals_id FROM meals WHERE meals_name = ".$meal." LIMIT 1");
      $row    = mysqli_fetch_assoc($result);

      if ($row) {
        $query_meal = ' AND meals_id = '.$row['meal_id'];
      }
    }

    $sql = 'SELECT menu_date, menu_description, type_name, meals_name FROM menu INNER JOIN meals ON menu.meals_id = meals.meals_id INNER JOIN type ON menu.type_id = type.type_id WHERE menu_deleted = 0 AND menu_date = '.$date;

    if ($row['meal_id'] != null) {
      $sql .= $query_meal;
    }

    $fichero = 'test.txt';
    // Abre el fichero para obtener el contenido existente
    $actual = file_get_contents($fichero);
    // Añade una nueva persona al fichero
    $actual .= $sql."\n";
    // Escribe el contenido al fichero
    file_put_contents($fichero, $actual);

    $result = $conn->query($sql);
    mysqli_close($conn);

    if ($result->num_rows > 0) {
      while($row = $result->fetch_assoc()) {
        $message = $message.'Para '.$result['meals_name'].' en el menú '.$result['type_name'].' hay '.$result['menu_description'];
      }
    }
    else {
      $message = "No se han encontrado resultados para tu solicitud. ";
    }
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


