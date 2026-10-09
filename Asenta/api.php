<?php
/* =====================================================
   API - Asentamientos
   Devuelve los datos en formato JSON
===================================================== */

require 'conexion.php';

header("Content-Type: application/json; charset=utf-8");

$tabla  = $_REQUEST['tabla']  ?? '';
$accion = $_REQUEST['accion'] ?? 'listar';

// Tablas permitidas (seguridad)
$permitidas = [
    'estado', 'municipio', 'ciudad',
    'tipo_asentamiento', 'zona', 'CP', 'asentamiento',
    'audit_estado', 'audit_municipio', 'audit_ciudad',
    'audit_tipo_asentamiento', 'audit_zona', 'audit_CP',
    'audit_asentamiento'
];

// ---------------- LISTAR AUDITORIAS (todas las tablas) ----------------
// Se valida ANTES que la tabla, porque no manda parametro "tabla"
if ($accion === 'listar_auditorias') {

    $todas = [];

    // ----- audit_estado -----
    $res = $conexion->query("SELECT id_audit, accion, usuario, fecha, c_estado, nombre FROM audit_estado");
    while ($fila = $res->fetch_assoc()) {
        $fila['tabla']   = 'Estado';
        $fila['detalle'] = "c_estado={$fila['c_estado']}, nombre={$fila['nombre']}";
        $todas[] = $fila;
    }

    // ----- audit_municipio -----
    $res = $conexion->query("SELECT id_audit, accion, usuario, fecha, c_estado, c_municipio, nombre FROM audit_municipio");
    while ($fila = $res->fetch_assoc()) {
        $fila['tabla']   = 'Municipio';
        $fila['detalle'] = "c_estado={$fila['c_estado']}, c_municipio={$fila['c_municipio']}, nombre={$fila['nombre']}";
        $todas[] = $fila;
    }

    // ----- audit_ciudad -----
    $res = $conexion->query("SELECT id_audit, accion, usuario, fecha, c_estado, c_municipio, c_cve_ciudad, d_ciudad FROM audit_ciudad");
    while ($fila = $res->fetch_assoc()) {
        $fila['tabla']   = 'Ciudad';
        $fila['detalle'] = "c_estado={$fila['c_estado']}, c_municipio={$fila['c_municipio']}, c_cve_ciudad={$fila['c_cve_ciudad']}, d_ciudad={$fila['d_ciudad']}";
        $todas[] = $fila;
    }

    // ----- audit_tipo_asentamiento -----
    $res = $conexion->query("SELECT id_audit, accion, usuario, fecha, c_tipo_asentamiento, d_tipo_asentamiento FROM audit_tipo_asentamiento");
    while ($fila = $res->fetch_assoc()) {
        $fila['tabla']   = 'Tipo de asentamiento';
        $fila['detalle'] = "c_tipo_asentamiento={$fila['c_tipo_asentamiento']}, d_tipo_asentamiento={$fila['d_tipo_asentamiento']}";
        $todas[] = $fila;
    }

    // ----- audit_zona -----
    $res = $conexion->query("SELECT id_audit, accion, usuario, fecha, c_zona, d_zona FROM audit_zona");
    while ($fila = $res->fetch_assoc()) {
        $fila['tabla']   = 'Zona';
        $fila['detalle'] = "c_zona={$fila['c_zona']}, d_zona={$fila['d_zona']}";
        $todas[] = $fila;
    }

    // ----- audit_CP -----
    $res = $conexion->query("SELECT id_audit, accion, usuario, fecha, c_CP, d_CP FROM audit_CP");
    while ($fila = $res->fetch_assoc()) {
        $fila['tabla']   = 'Codigo postal';
        $fila['detalle'] = "c_CP={$fila['c_CP']}, d_CP={$fila['d_CP']}";
        $todas[] = $fila;
    }

    // ----- audit_asentamiento -----
    $res = $conexion->query("SELECT id_audit, accion, usuario, fecha, d_codigo, c_estado, c_municipio, id_asenta_cpcons, c_tipo_asentamiento, c_zona, c_CP, d_asenta FROM audit_asentamiento");
    while ($fila = $res->fetch_assoc()) {
        $fila['tabla']   = 'Asentamiento';
        $fila['detalle'] = "d_codigo={$fila['d_codigo']}, c_estado={$fila['c_estado']}, c_municipio={$fila['c_municipio']}, id_asenta={$fila['id_asenta_cpcons']}, tipo={$fila['c_tipo_asentamiento']}, zona={$fila['c_zona']}, c_CP={$fila['c_CP']}, d_asenta={$fila['d_asenta']}";
        $todas[] = $fila;
    }

    // Ordenar por fecha descendente
    usort($todas, function($a, $b){
        return strcmp($b['fecha'], $a['fecha']);
    });

    // Limitar a 500
    $todas = array_slice($todas, 0, 500);

    // Limpiar campos sobrantes que no queremos mandar al front
    foreach ($todas as &$fila) {
        unset($fila['c_estado'], $fila['c_municipio'], $fila['c_cve_ciudad'],
              $fila['c_tipo_asentamiento'], $fila['c_zona'], $fila['c_CP'],
              $fila['d_codigo'], $fila['id_asenta_cpcons']);
    }
    unset($fila);

    echo json_encode(["ok" => true, "datos" => $todas]);
    exit;
}

// ---------------- VALIDACION DE TABLA ----------------
if (!in_array($tabla, $permitidas)) {
    echo json_encode(["ok" => false, "error" => "Tabla no permitida"]);
    exit;
}

// ---------------- LISTAR ----------------
if ($accion === 'listar') {

    $sql = "SELECT * FROM `$tabla`";
    $res = $conexion->query($sql);

    if (!$res) {
        echo json_encode(["ok" => false, "error" => $conexion->error]);
        exit;
    }

    $datos = [];
    while ($fila = $res->fetch_assoc()) {
        $datos[] = $fila;
    }

    echo json_encode(["ok" => true, "datos" => $datos]);
    exit;
}

// ---------------- INSERTAR ----------------
if ($accion === 'insertar') {

    $campos = $_POST['campos'] ?? [];

    if (empty($campos)) {
        echo json_encode(["ok" => false, "error" => "Sin datos"]);
        exit;
    }

    $cols = array_keys($campos);
    $cols_sql = "`" . implode("`,`", $cols) . "`";
    $placeholders = implode(",", array_fill(0, count($cols), "?"));

    $stmt = $conexion->prepare("INSERT INTO `$tabla` ($cols_sql) VALUES ($placeholders)");

    if (!$stmt) {
        echo json_encode(["ok" => false, "error" => $conexion->error]);
        exit;
    }

    $tipos = str_repeat("s", count($cols));
    $stmt->bind_param($tipos, ...array_values($campos));
    $ok = $stmt->execute();

    if (!$ok) {
        echo json_encode(["ok" => false, "error" => $stmt->error]);
        exit;
    }

    echo json_encode(["ok" => true, "id" => $conexion->insert_id]);
    exit;
}

// ---------------- ACTUALIZAR ----------------
if ($accion === 'actualizar') {

    $campos = $_POST['campos'] ?? [];
    $where  = $_POST['where']  ?? [];

    if (empty($campos) || empty($where)) {
        echo json_encode(["ok" => false, "error" => "Datos incompletos"]);
        exit;
    }

    $set = [];
    foreach (array_keys($campos) as $c) {
        $set[] = "`$c` = ?";
    }
    $set_sql = implode(", ", $set);

    $cond = [];
    foreach (array_keys($where) as $c) {
        $cond[] = "`$c` = ?";
    }
    $cond_sql = implode(" AND ", $cond);

    $stmt = $conexion->prepare("UPDATE `$tabla` SET $set_sql WHERE $cond_sql");

    if (!$stmt) {
        echo json_encode(["ok" => false, "error" => $conexion->error]);
        exit;
    }

    $valores = array_merge(array_values($campos), array_values($where));
    $tipos = str_repeat("s", count($valores));
    $stmt->bind_param($tipos, ...$valores);
    $ok = $stmt->execute();

    if (!$ok) {
        echo json_encode(["ok" => false, "error" => $stmt->error]);
        exit;
    }

    echo json_encode(["ok" => true, "afectados" => $stmt->affected_rows]);
    exit;
}

// ---------------- ELIMINAR ----------------
if ($accion === 'eliminar') {

    $where = $_POST['where'] ?? [];

    if (empty($where)) {
        echo json_encode(["ok" => false, "error" => "Sin condiciones"]);
        exit;
    }

    $cond = [];
    foreach (array_keys($where) as $c) {
        $cond[] = "`$c` = ?";
    }
    $cond_sql = implode(" AND ", $cond);

    $stmt = $conexion->prepare("DELETE FROM `$tabla` WHERE $cond_sql");

    if (!$stmt) {
        echo json_encode(["ok" => false, "error" => $conexion->error]);
        exit;
    }

    $valores = array_values($where);
    $tipos = str_repeat("s", count($valores));
    $stmt->bind_param($tipos, ...$valores);
    $ok = $stmt->execute();

    if (!$ok) {
        echo json_encode(["ok" => false, "error" => $stmt->error]);
        exit;
    }

    echo json_encode(["ok" => true, "afectados" => $stmt->affected_rows]);
    exit;
}

echo json_encode(["ok" => false, "error" => "Accion no valida"]);