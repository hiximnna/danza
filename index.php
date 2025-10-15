<?php
// index.php - Sitio web para la base de datos 'danza_moderna' y la tabla 'alumnos_danza'
// Modificado para usar la estructura con id_alumno, nombre, edad, nivel, grupo, cuota_mensual, asistencias, fecha_registro

$dbHost = '127.0.0.1';
$dbUser = 'root';
$dbPass = ''; // cambia si tu MySQL tiene contraseña
$dbName = 'danza_moderna';
$table  = 'alumnos_danza';

try {
    $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("Error al conectar con el servidor MySQL: " . htmlspecialchars($e->getMessage()));
}

$pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci");
$pdo->exec("USE `$dbName`");

// Crear tabla según las especificaciones dadas
$createSQL = "CREATE TABLE IF NOT EXISTS `$table` (
  id_alumno INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(100),
  edad INT,
  nivel VARCHAR(20),
  grupo VARCHAR(10),
  cuota_mensual DECIMAL(6,2),
  asistencias INT,
  fecha_registro DATE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$pdo->exec($createSQL);

$action = $_REQUEST['action'] ?? 'list';

if ($action === 'insert_sample') {
    $samples = [
        ['Lucía',18,'Intermedio','A1',350.00,15,'2024-09-01'],
        ['María',20,'Avanzado','B2',400.00,12,'2023-06-15'],
        ['Ana',16,'Principiante','A2',300.00,20,'2025-01-10'],
        ['Sofía',22,'Avanzado','B1',420.00,10,'2022-11-03'],
        ['Carla',19,'Intermedio','A3',350.00,17,'2024-03-21'],
        ['Paola',21,'Avanzado','B3',400.00,8,'2021-07-30'],
        ['Elena',17,'Principiante','A1',280.00,18,'2024-08-12'],
    ];
    $stmt = $pdo->prepare("INSERT INTO `$table` (nombre, edad, nivel, grupo, cuota_mensual, asistencias, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($samples as $s) $stmt->execute($s);
    header("Location: ?action=list&msg=" . urlencode("Se insertaron registros de ejemplo."));
    exit;
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO `$table` (nombre, edad, nivel, grupo, cuota_mensual, asistencias, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['nombre'], $_POST['edad'] ?: null, $_POST['nivel'], $_POST['grupo'], $_POST['cuota_mensual'], $_POST['asistencias'], $_POST['fecha_registro'] ?: null
    ]);
    header('Location: ?action=list&msg=' . urlencode('Alumno agregado.'));
    exit;
}

if ($action === 'delete' && isset($_GET['id_alumno'])) {
    $id = (int)$_GET['id_alumno'];
    $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id_alumno = ?");
    $stmt->execute([$id]);
    header('Location: ?action=list&msg=' . urlencode('Registro eliminado.'));
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id_alumno'] ?? 0);
    $stmt = $pdo->prepare("UPDATE `$table` SET nombre=?, edad=?, nivel=?, grupo=?, cuota_mensual=?, asistencias=?, fecha_registro=? WHERE id_alumno=?");
    $stmt->execute([
        $_POST['nombre'], $_POST['edad'], $_POST['nivel'], $_POST['grupo'], $_POST['cuota_mensual'], $_POST['asistencias'], $_POST['fecha_registro'], $id
    ]);
    header('Location: ?action=list&msg=' . urlencode('Registro actualizado.'));
    exit;
}

function fetchAll($pdo, $table) {
    $stmt = $pdo->query("SELECT * FROM `$table` ORDER BY id_alumno ASC");
    return $stmt->fetchAll();
}

$rows = fetchAll($pdo, $table);

$editing = null;
if ($action === 'edit' && isset($_GET['id_alumno'])) {
    $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id_alumno = ?");
    $stmt->execute([(int)$_GET['id_alumno']]);
    $editing = $stmt->fetch();
}

$msg = $_GET['msg'] ?? null;
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Danza Moderna - Alumnos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container">
<h1 class="mb-3">Danza Moderna — Alumnos</h1>
<?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="mb-3">
<a class="btn btn-outline-primary" href="?action=list">Mostrar todos</a>
<a class="btn btn-outline-secondary" href="?action=insert_sample">Insertar ejemplo</a>
</div>
<div class="row">
<div class="col-md-7">
<table class="table table-bordered bg-white">
<thead><tr><th>ID</th><th>Nombre</th><th>Edad</th><th>Nivel</th><th>Grupo</th><th>Cuota</th><th>Asist.</th><th>Registro</th><th>Acciones</th></tr></thead>
<tbody>
<?php if (!$rows): ?><tr><td colspan="9" class="text-center">Sin registros</td></tr><?php else: ?>
<?php foreach ($rows as $r): ?>
<tr>
<td><?php echo $r['id_alumno'] ?></td>
<td><?php echo htmlspecialchars($r['nombre']) ?></td>
<td><?php echo htmlspecialchars($r['edad']) ?></td>
<td><?php echo htmlspecialchars($r['nivel']) ?></td>
<td><?php echo htmlspecialchars($r['grupo']) ?></td>
<td>$<?php echo htmlspecialchars($r['cuota_mensual']) ?></td>
<td><?php echo htmlspecialchars($r['asistencias']) ?></td>
<td><?php echo htmlspecialchars($r['fecha_registro']) ?></td>
<td><a href="?action=edit&id_alumno=<?php echo $r['id_alumno'] ?>" class="btn btn-sm btn-outline-secondary">Editar</a> <a href="?action=delete&id_alumno=<?php echo $r['id_alumno'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar?')">Eliminar</a></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
<div class="col-md-5">
<div class="card"><div class="card-body">
<?php if ($editing): ?>
<h5>Editar alumno #<?php echo $editing['id_alumno'] ?></h5>
<form method="post" action="?action=update">
<input type="hidden" name="id_alumno" value="<?php echo $editing['id_alumno'] ?>">
<?php $data=$editing; include 'formulario_danza.php'; ?>
<button class="btn btn-primary mt-2">Guardar</button>
<a href="?action=list" class="btn btn-secondary mt-2">Cancelar</a>
</form>
<?php else: ?>
<h5>Agregar nuevo alumno</h5>
<form method="post" action="?action=add">
<div class="mb-2"><label>Nombre</label><input name="nombre" class="form-control" required></div>
<div class="mb-2"><label>Edad</label><input name="edad" type="number" class="form-control"></div>
<div class="mb-2"><label>Nivel</label><input name="nivel" class="form-control"></div>
<div class="mb-2"><label>Grupo</label><input name="grupo" class="form-control"></div>
<div class="mb-2"><label>Cuota mensual</label><input name="cuota_mensual" type="number" step="0.01" class="form-control"></div>
<div class="mb-2"><label>Asistencias</label><input name="asistencias" type="number" class="form-control"></div>
<div class="mb-2"><label>Fecha registro</label><input name="fecha_registro" type="date" class="form-control"></div>
<button class="btn btn-success mt-2">Agregar</button>
</form>
<?php endif; ?>
</div></div>
</div>
</div>
</div>
</body>
</html>
