<?php
// API unificada: Chatbot + Contenido educativo (unifica chatbot_api_clean e info_sordos_api)
// Mantiene compatibilidad con pruebas que llaman a este endpoint.

// Logging simple
function api_log($msg){
    $ts=date('Y-m-d H:i:s');
    @file_put_contents(__DIR__.DIRECTORY_SEPARATOR.'chatbot_clean_debug.log',"[$ts] ".$msg.PHP_EOL,FILE_APPEND|LOCK_EX);
}

// Headers CORS/JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { api_log('OPTIONS preflight'); http_response_code(204); exit; }

// Dataset educativo (extracto clave, ampliable)
$INFO = [
    'definicion' => [
        'titulo' => '¿Qué es la sordera?',
        'descripcion' => 'Pérdida total o parcial de la capacidad auditiva. Puede ser congénita o adquirida y se clasifica por grado y tipo.'
    ],
    'causas_principales' => [
        'titulo' => 'Principales causas de sordera',
        'congenitas' => ['Genéticas 50-60%', 'Infecciones maternas (rubéola/CMV) 15-20%', 'Complicaciones perinatales 10-15%'],
        'adquiridas' => ['Ruido intenso', 'Infecciones (meningitis/otitis crónica)', 'Medicamentos ototóxicos', 'Traumatismos', 'Envejecimiento (presbiacusia)']
    ],
    'lengua_señas_colombiana' => [
        'titulo' => 'Lengua de Señas Colombiana (LSC)',
        'descripcion' => 'Lengua visual-espacial reconocida por las leyes 324/1996 y 982/2005; gramática propia; ~450k usuarios.'
    ],
    'cultura_sorda' => [
        'titulo' => 'Cultura Sorda',
        'definicion' => 'Identidad cultural basada en la LSC, valores comunitarios y perspectiva visual del mundo.'
    ],
    'tecnologias_apoyo' => [
        'titulo' => 'Tecnologías de Apoyo',
        'items' => ['Audífonos (BTE/ITE/ITC/CIC)', 'Implantes cocleares', 'Sistemas FM', 'Apps voz-texto', 'Alertas visuales/vibración']
    ],
    'grados_perdida' => [
        'titulo' => 'Grados de Pérdida Auditiva',
        'clasificacion' => [
            ['grado' => 'Leve', 'rango' => '21-40 dB'],
            ['grado' => 'Moderada', 'rango' => '41-70 dB'],
            ['grado' => 'Severa', 'rango' => '71-90 dB'],
            ['grado' => 'Profunda', 'rango' => '91+ dB']
        ]
    ]
];

// Chatbot simple con detección de sección y respuesta enriquecida usando $INFO
class UnifiedChatbot {
    private $pal = [
        'causas_principales' => ['causas','causa','por qué','origen','genética','congénito','hereditario','infecciones','ruido','medicamentos','traumatismo','meningitis','otitis','ototóxicos'],
        'definicion' => ['qué es','definición','concepto','tipos','sordera','pérdida auditiva','hipoacusia','anacusia','deficiencia auditiva'],
        'lengua_señas_colombiana' => ['lsc','lengua de señas','señas','lenguaje de señas','gestos','comunicación visual','señas colombianas'],
        'cultura_sorda' => ['cultura sorda','comunidad sorda','identidad sorda','valores','tradiciones','arte sordo','teatro sordo'],
        'tecnologias_apoyo' => ['audífonos','implante coclear','tecnología','dispositivos','apps','aplicaciones','ayudas técnicas','sistemas fm'],
        'grados_perdida' => ['grados','niveles','decibeles','leve','moderada','severa','profunda','dB']
    ];

    public function procesar($m, $info){
        $m = trim((string)$m);
        if ($m==='') return $this->fallback();
        $sec = $this->det($m);
        if ($sec && isset($info[$sec])) {
            return $this->formatearDesdeInfo($sec, $info[$sec]);
        }
        // Respuestas rápidas si no hay match en info
        $resp = [
            'definicion' => "🔍 La sordera es la pérdida total o parcial de la audición.",
            'lengua_señas_colombiana' => "🤟 LSC: lengua visual-espacial oficial en Colombia.",
            'cultura_sorda' => "🎭 Cultura sorda: identidad visual y lengua de señas.",
            'tecnologias_apoyo' => "🔧 Audífonos, implantes, apps y alertas visuales.",
            'causas_principales' => "📊 Causas: genéticas, infecciones, ruido, fármacos, traumatismos, edad."
        ];
        return $sec && isset($resp[$sec]) ? $resp[$sec] : $this->fallback();
    }

    public function sugerencias(){
        return [
            "¿Qué es la sordera?",
            "¿Cuáles son las causas de la sordera?",
            "¿Qué es la LSC?",
            "¿Cómo comunicarse con personas sordas?",
            "Tecnologías de apoyo auditivo"
        ];
    }

    private function det($m){
        $m=mb_strtolower($m,'UTF-8');$best=null;$bestS=0;
        foreach($this->pal as $s=>$ps){$sc=0;foreach($ps as $p){if(mb_strpos($m,mb_strtolower($p,'UTF-8'))!==false)$sc+=mb_strlen($p,'UTF-8');}if($sc>$bestS){$bestS=$sc;$best=$s;}}
        return $best;
    }

    private function formatearDesdeInfo($sec, $data){
        switch ($sec) {
            case 'definicion':
                return "🔍 ".$data['titulo']."\n\n".$data['descripcion'];
            case 'causas_principales':
                $c = $data;
                $txt = "📊 ".$c['titulo']."\n\n";
                $txt .= "🧬 Congénitas: • ".implode(" • ",$c['congenitas'])."\n";
                $txt .= "⚡ Adquiridas: • ".implode(" • ",$c['adquiridas'])."\n\n";
                $txt .= "¿Quieres detalles de alguna causa?";
                return $txt;
            case 'lengua_señas_colombiana':
                return "🤟 ".$data['titulo']."\n\n".$data['descripcion'];
            case 'cultura_sorda':
                return "👥 ".$data['titulo']."\n\n".$data['definicion'];
            case 'tecnologias_apoyo':
                return "🔧 ".$data['titulo']."\n\n• ".implode("\n• ",$data['items']);
            case 'grados_perdida':
                $l = array_map(function($g){return $g['grado']." (".$g['rango'].")";}, $data['clasificacion']);
                return "📏 ".$data['titulo']."\n\n• ".implode("\n• ",$l);
            default:
                return "📚 Información disponible sobre ".$sec.", indica qué detalle necesitas.";
        }
    }

    private function fallback(){
        return "No estoy seguro de entender tu pregunta. Puedo ayudarte con: causas de sordera, LSC, cultura sorda, tecnologías de apoyo y educación inclusiva. ¿Podrías ser más específico?";
    }
}

// Utilidades de búsqueda sobre $INFO
function buscarEnInfo($info, $termino){
    $t = mb_strtolower($termino,'UTF-8');
    $res = [];
    foreach ($info as $sec => $contenido) {
        $json = mb_strtolower(json_encode($contenido, JSON_UNESCAPED_UNICODE), 'UTF-8');
        if (strpos($json, $t) !== false) {
            $res[$sec] = $contenido;
        }
    }
    return $res;
}

try {
    api_log('Nueva solicitud: '.$_SERVER['REQUEST_METHOD'].' IP '.($_SERVER['REMOTE_ADDR']??'unknown'));

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // GET: compatibilidad de info API
        if (isset($_GET['seccion'])) {
            $sec = $_GET['seccion'];
            if (isset($INFO[$sec])) {
                echo json_encode(['success'=>true,'seccion'=>$sec,'data'=>$INFO[$sec]], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
            } else {
                echo json_encode(['success'=>false,'error'=>'Sección no encontrada','secciones_disponibles'=>array_keys($INFO)], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
            }
            exit;
        }
        echo json_encode([
            'success'=>true,
            'message'=>'API unificada Chatbot + Info',
            'secciones_disponibles'=>array_keys($INFO),
            'uso'=>'GET ?seccion=nombre | POST {"mensaje":"..."} | POST {"buscar":"..."}'
        ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success'=>false,'error'=>'Método no permitido','metodos_soportados'=>['GET','POST']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $raw = file_get_contents('php://input');
    api_log('Body: '.substr($raw,0,200));
    $data = $raw ? json_decode($raw, true) : null;

    // POST: búsqueda en info
    if ($data && isset($data['buscar'])) {
        $termino = (string)$data['buscar'];
        $resultados = buscarEnInfo($INFO, $termino);
        echo json_encode([
            'success'=>true,
            'termino_busqueda'=>$termino,
            'resultados_encontrados'=>count($resultados),
            'data'=>$resultados
        ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        exit;
    }

    // POST: chatbot
    $mensaje = $data['mensaje'] ?? ($_POST['mensaje'] ?? '');
    api_log('Mensaje: '.(is_string($mensaje)?$mensaje:json_encode($mensaje)));
    $bot = new UnifiedChatbot();
    $respuesta = $bot->procesar($mensaje, $INFO);
    $payload = [
        'success' => true,
        'respuesta' => $respuesta,
        'sugerencias' => $bot->sugerencias(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    api_log('OK respuesta: '.substr($respuesta,0,120));
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    api_log('ERROR: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Error interno'], JSON_UNESCAPED_UNICODE);
}
