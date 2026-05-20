<?php
session_start();
 
// ─── CONFIGURACIÓN ────────────────────────────────────────────────────────────
define('ADMIN_PASSWORD', 'axsoft2024');   // Cambia esta contraseña
define('DATA_FILE', __DIR__ . '/data/services.json');
 
// ─── HELPERS ──────────────────────────────────────────────────────────────────
function loadServices(): array {
    if (!file_exists(DATA_FILE)) return [];
    $json = file_get_contents(DATA_FILE);
    return json_decode($json, true) ?? [];
}
 
function saveServices(array $services): void {
    if (!is_dir(dirname(DATA_FILE))) {
        mkdir(dirname(DATA_FILE), 0755, true);
    }
    file_put_contents(DATA_FILE, json_encode($services, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
 
function isAdmin(): bool {
    return isset($_SESSION['admin']) && $_SESSION['admin'] === true;
}
 
function nextId(array $services): int {
    if (empty($services)) return 1;
    return max(array_column($services, 'id')) + 1;
}
 
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
 
// Iconos SVG por categoría
function getIcon(string $icon): string {
    $icons = [
        'web'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 010 20M12 2a15.3 15.3 0 000 20"/></svg>',
        'mobile'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="5" y="2" width="14" height="20" rx="2"/><circle cx="12" cy="18" r="1"/></svg>',
        'cloud'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 10h-1.26A8 8 0 109 20h9a5 5 0 000-10z"/></svg>',
        'security'=> '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'data'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>',
        'ai'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2a4 4 0 014 4v1h1a3 3 0 013 3v6a3 3 0 01-3 3H7a3 3 0 01-3-3V10a3 3 0 013-3h1V6a4 4 0 014-4z"/><circle cx="9" cy="13" r="1" fill="currentColor"/><circle cx="15" cy="13" r="1" fill="currentColor"/></svg>',
        'support' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>',
        'design'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="13.5" cy="6.5" r="4"/><path d="M13.5 10.5v3"/><path d="M7 22l3.5-7 3.5 7"/><path d="M9 18h5"/></svg>',
    ];
    return $icons[$icon] ?? $icons['web'];
}
 
// ─── ACCIONES POST ─────────────────────────────────────────────────────────────
$error = '';
$success = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
 
    // LOGIN
    if ($action === 'login') {
        if ($_POST['password'] === ADMIN_PASSWORD) {
            $_SESSION['admin'] = true;
            header('Location: ?panel');
            exit;
        } else {
            $error = 'Contraseña incorrecta.';
        }
    }
 
    // LOGOUT
    if ($action === 'logout') {
        session_destroy();
        header('Location: ?');
        exit;
    }
 
    // CRUD — requiere autenticación
    if (isAdmin()) {
        $services = loadServices();
 
        if ($action === 'add') {
            $title = trim($_POST['title'] ?? '');
            $desc  = trim($_POST['description'] ?? '');
            $icon  = trim($_POST['icon'] ?? 'web');
            $tag   = trim($_POST['tag'] ?? '');
            if ($title && $desc) {
                $services[] = [
                    'id'          => nextId($services),
                    'title'       => $title,
                    'description' => $desc,
                    'icon'        => $icon,
                    'tag'         => $tag,
                    'created_at'  => date('Y-m-d H:i:s'),
                ];
                saveServices($services);
                $success = 'Servicio agregado correctamente.';
            } else {
                $error = 'Título y descripción son obligatorios.';
            }
        }
 
        if ($action === 'edit') {
            $id    = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $desc  = trim($_POST['description'] ?? '');
            $icon  = trim($_POST['icon'] ?? 'web');
            $tag   = trim($_POST['tag'] ?? '');
            if ($title && $desc) {
                foreach ($services as &$s) {
                    if ($s['id'] === $id) {
                        $s['title']       = $title;
                        $s['description'] = $desc;
                        $s['icon']        = $icon;
                        $s['tag']         = $tag;
                        break;
                    }
                }
                unset($s);
                saveServices($services);
                $success = 'Servicio actualizado correctamente.';
            } else {
                $error = 'Título y descripción son obligatorios.';
            }
        }
 
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $services = array_values(array_filter($services, fn($s) => $s['id'] !== $id));
            saveServices($services);
            $success = 'Servicio eliminado.';
        }
    }
}
 
// ─── DETERMINAR VISTA ─────────────────────────────────────────────────────────
$view = 'public';
if (isset($_GET['panel']))  $view = isAdmin() ? 'panel' : 'login';
if (isset($_GET['login']))  $view = 'login';
 
$services = loadServices();
 
// Servicio a editar
$editService = null;
if ($view === 'panel' && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($services as $s) {
        if ($s['id'] === $editId) { $editService = $s; break; }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AXSoft<?= $view === 'panel' ? ' — Panel Admin' : ' — Soluciones de Software' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">
<style>
/* ═══════════════════════════════════════════════════════
   VARIABLES Y RESET
═══════════════════════════════════════════════════════ */
:root {
  --bg:        #04080f;
  --bg2:       #080e1a;
  --surface:   #0d1526;
  --surface2:  #121e34;
  --border:    #1e2f50;
  --accent:    #00d4ff;
  --accent2:   #7b61ff;
  --accent3:   #ff6b6b;
  --text:      #e8eef8;
  --text-muted:#6b7fa3;
  --text-dim:  #3d5275;
  --font-head: 'Syne', sans-serif;
  --font-body: 'DM Sans', sans-serif;
  --radius:    12px;
  --radius-lg: 20px;
  --shadow:    0 0 40px rgba(0,212,255,.08);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  font-family:var(--font-body);
  background:var(--bg);
  color:var(--text);
  min-height:100vh;
  overflow-x:hidden;
}
a{color:var(--accent);text-decoration:none}
a:hover{text-decoration:underline}
img{max-width:100%}
 
/* ═══════════════════════════════════════════════════════
   UTILIDADES
═══════════════════════════════════════════════════════ */
.container{max-width:1180px;margin:0 auto;padding:0 24px}
.btn{
  display:inline-flex;align-items:center;gap:8px;
  padding:10px 22px;border-radius:8px;border:none;
  font-family:var(--font-body);font-size:.9rem;font-weight:500;
  cursor:pointer;transition:all .2s;text-decoration:none;
}
.btn-primary{background:var(--accent);color:#000;font-weight:600}
.btn-primary:hover{background:#33deff;transform:translateY(-1px);box-shadow:0 4px 20px rgba(0,212,255,.4)}
.btn-secondary{background:var(--surface2);color:var(--text);border:1px solid var(--border)}
.btn-secondary:hover{border-color:var(--accent);color:var(--accent)}
.btn-danger{background:transparent;color:var(--accent3);border:1px solid rgba(255,107,107,.3)}
.btn-danger:hover{background:var(--accent3);color:#fff}
.btn-sm{padding:6px 14px;font-size:.82rem}
 
/* ═══════════════════════════════════════════════════════
   NAVBAR
═══════════════════════════════════════════════════════ */
.navbar{
  position:fixed;top:0;left:0;right:0;z-index:100;
  padding:16px 0;
  background:rgba(4,8,15,.85);
  backdrop-filter:blur(20px);
  border-bottom:1px solid var(--border);
}
.navbar .container{display:flex;align-items:center;justify-content:space-between}
.nav-logo{
  font-family:var(--font-head);font-size:1.5rem;font-weight:800;
  color:var(--text);letter-spacing:-.02em;
}
.nav-logo span{color:var(--accent)}
.nav-links{display:flex;align-items:center;gap:8px}
 
/* ═══════════════════════════════════════════════════════
   HERO
═══════════════════════════════════════════════════════ */
.hero{
  min-height:100vh;display:flex;align-items:center;
  padding:140px 0 80px;
  position:relative;overflow:hidden;
}
.hero::before{
  content:'';position:absolute;inset:0;
  background:
    radial-gradient(ellipse 80% 60% at 60% 30%, rgba(0,212,255,.07) 0%, transparent 70%),
    radial-gradient(ellipse 60% 50% at 20% 80%, rgba(123,97,255,.06) 0%, transparent 70%);
}
.hero-grid{
  position:absolute;inset:0;
  background-image:
    linear-gradient(var(--border) 1px, transparent 1px),
    linear-gradient(90deg, var(--border) 1px, transparent 1px);
  background-size:60px 60px;
  mask-image:radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%);
  opacity:.3;
}
.hero-content{position:relative;max-width:680px}
.hero-badge{
  display:inline-flex;align-items:center;gap:8px;
  background:rgba(0,212,255,.08);border:1px solid rgba(0,212,255,.2);
  color:var(--accent);padding:6px 14px;border-radius:100px;
  font-size:.82rem;font-weight:500;margin-bottom:28px;
  animation:fadeUp .6s ease both;
}
.hero-badge::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--accent);animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
.hero h1{
  font-family:var(--font-head);font-size:clamp(2.8rem,6vw,4.5rem);
  font-weight:800;line-height:1.05;letter-spacing:-.04em;
  animation:fadeUp .6s .1s ease both;
}
.hero h1 em{
  font-style:normal;
  background:linear-gradient(135deg,var(--accent),var(--accent2));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
}
.hero p{
  margin-top:20px;font-size:1.1rem;line-height:1.7;
  color:var(--text-muted);max-width:520px;
  animation:fadeUp .6s .2s ease both;
}
.hero-cta{
  margin-top:36px;display:flex;gap:12px;flex-wrap:wrap;
  animation:fadeUp .6s .3s ease both;
}
.hero-stats{
  margin-top:64px;display:flex;gap:40px;flex-wrap:wrap;
  animation:fadeUp .6s .4s ease both;
}
.stat-item{}
.stat-num{
  font-family:var(--font-head);font-size:2rem;font-weight:800;
  color:var(--accent);
}
.stat-label{font-size:.82rem;color:var(--text-muted);margin-top:2px}
 
/* ═══════════════════════════════════════════════════════
   SERVICIOS PÚBLICOS
═══════════════════════════════════════════════════════ */
.section{padding:100px 0}
.section-header{text-align:center;margin-bottom:64px}
.section-tag{
  display:inline-block;
  background:rgba(123,97,255,.1);border:1px solid rgba(123,97,255,.25);
  color:var(--accent2);padding:5px 14px;border-radius:100px;
  font-size:.8rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;
  margin-bottom:16px;
}
.section-title{
  font-family:var(--font-head);font-size:clamp(2rem,4vw,3rem);
  font-weight:800;letter-spacing:-.03em;
}
.section-sub{margin-top:12px;color:var(--text-muted);font-size:1rem;max-width:500px;margin-inline:auto}
 
.services-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(320px,1fr));
  gap:24px;
}
.service-card{
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:var(--radius-lg);
  padding:32px;
  position:relative;
  transition:all .3s;
  overflow:hidden;
}
.service-card::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(0,212,255,.04) 0%,transparent 60%);
  opacity:0;transition:.3s;
}
.service-card:hover{
  border-color:rgba(0,212,255,.3);
  transform:translateY(-4px);
  box-shadow:0 20px 40px rgba(0,0,0,.3),0 0 0 1px rgba(0,212,255,.1);
}
.service-card:hover::before{opacity:1}
.card-icon{
  width:52px;height:52px;
  background:linear-gradient(135deg,rgba(0,212,255,.15),rgba(123,97,255,.1));
  border:1px solid rgba(0,212,255,.2);
  border-radius:14px;display:flex;align-items:center;justify-content:center;
  color:var(--accent);margin-bottom:20px;
}
.card-icon svg{width:24px;height:24px}
.card-tag{
  position:absolute;top:20px;right:20px;
  background:rgba(123,97,255,.15);border:1px solid rgba(123,97,255,.25);
  color:var(--accent2);font-size:.72rem;font-weight:600;
  padding:3px 10px;border-radius:100px;letter-spacing:.05em;text-transform:uppercase;
}
.card-title{
  font-family:var(--font-head);font-size:1.2rem;font-weight:700;
  margin-bottom:10px;
}
.card-desc{
  color:var(--text-muted);font-size:.92rem;line-height:1.65;
}
 
/* EMPTY STATE */
.empty-state{
  grid-column:1/-1;text-align:center;padding:80px 20px;
  color:var(--text-dim);
}
.empty-state svg{width:64px;height:64px;margin:0 auto 20px;opacity:.3;display:block}
.empty-state p{font-size:1rem}
 
/* ═══════════════════════════════════════════════════════
   CTA SECTION
═══════════════════════════════════════════════════════ */
.cta-section{
  padding:80px 0;
  background:linear-gradient(135deg,rgba(0,212,255,.06),rgba(123,97,255,.06));
  border-top:1px solid var(--border);
  border-bottom:1px solid var(--border);
  text-align:center;
}
.cta-section h2{
  font-family:var(--font-head);font-size:2.2rem;font-weight:800;
  margin-bottom:16px;
}
.cta-section p{color:var(--text-muted);margin-bottom:32px;font-size:1rem}
 
/* ═══════════════════════════════════════════════════════
   FOOTER
═══════════════════════════════════════════════════════ */
footer{
  padding:40px 0;border-top:1px solid var(--border);
  text-align:center;color:var(--text-dim);font-size:.85rem;
}
footer strong{color:var(--accent)}
 
/* ═══════════════════════════════════════════════════════
   LOGIN PAGE
═══════════════════════════════════════════════════════ */
.login-page{
  min-height:100vh;display:flex;align-items:center;justify-content:center;
  background:var(--bg);
  position:relative;
}
.login-page::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse 70% 70% at 50% 50%,rgba(0,212,255,.05),transparent);
}
.login-box{
  position:relative;
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:var(--radius-lg);
  padding:48px 40px;
  width:100%;max-width:420px;
  box-shadow:0 40px 80px rgba(0,0,0,.4);
  animation:fadeUp .5s ease;
}
.login-logo{
  font-family:var(--font-head);font-size:2rem;font-weight:800;
  text-align:center;margin-bottom:8px;
}
.login-logo span{color:var(--accent)}
.login-sub{text-align:center;color:var(--text-muted);font-size:.9rem;margin-bottom:36px}
.form-group{margin-bottom:20px}
.form-group label{
  display:block;font-size:.85rem;font-weight:500;
  color:var(--text-muted);margin-bottom:8px;
}
.form-group input,
.form-group textarea,
.form-group select{
  width:100%;
  background:var(--bg2);
  border:1px solid var(--border);
  border-radius:8px;
  padding:12px 16px;
  color:var(--text);
  font-family:var(--font-body);font-size:.92rem;
  transition:.2s;outline:none;
}
.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus{
  border-color:var(--accent);
  box-shadow:0 0 0 3px rgba(0,212,255,.1);
}
.form-group textarea{resize:vertical;min-height:90px}
.form-group select option{background:var(--surface)}
.alert{
  padding:12px 16px;border-radius:8px;font-size:.88rem;
  margin-bottom:20px;
}
.alert-error{background:rgba(255,107,107,.1);border:1px solid rgba(255,107,107,.3);color:var(--accent3)}
.alert-success{background:rgba(0,212,255,.08);border:1px solid rgba(0,212,255,.25);color:var(--accent)}
 
/* ═══════════════════════════════════════════════════════
   PANEL ADMIN
═══════════════════════════════════════════════════════ */
.panel-layout{
  display:grid;grid-template-columns:260px 1fr;min-height:100vh;
}
.sidebar{
  background:var(--bg2);
  border-right:1px solid var(--border);
  padding:28px 20px;
  position:sticky;top:0;height:100vh;overflow-y:auto;
}
.sidebar-logo{
  font-family:var(--font-head);font-size:1.4rem;font-weight:800;
  margin-bottom:4px;
}
.sidebar-logo span{color:var(--accent)}
.sidebar-role{font-size:.78rem;color:var(--text-dim);margin-bottom:32px}
.sidebar-nav{list-style:none;display:flex;flex-direction:column;gap:4px}
.sidebar-nav a{
  display:flex;align-items:center;gap:10px;
  padding:10px 14px;border-radius:8px;color:var(--text-muted);
  font-size:.9rem;transition:.2s;
}
.sidebar-nav a:hover,.sidebar-nav a.active{
  background:var(--surface);color:var(--accent);text-decoration:none;
}
.sidebar-nav svg{width:18px;height:18px;flex-shrink:0}
.sidebar-divider{height:1px;background:var(--border);margin:16px 0}
 
.panel-main{padding:40px 48px;background:var(--bg)}
.panel-header{
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:36px;
}
.panel-title{
  font-family:var(--font-head);font-size:1.8rem;font-weight:800;
}
.panel-subtitle{color:var(--text-muted);font-size:.9rem;margin-top:4px}
 
/* Stats cards */
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:40px}
.stat-card{
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--radius);padding:24px;
}
.stat-card .num{
  font-family:var(--font-head);font-size:2rem;font-weight:800;color:var(--accent);
}
.stat-card .lbl{font-size:.82rem;color:var(--text-muted);margin-top:4px}
 
/* Form panel */
.form-panel{
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--radius-lg);padding:32px;margin-bottom:32px;
}
.form-panel-title{
  font-family:var(--font-head);font-size:1.1rem;font-weight:700;
  margin-bottom:24px;display:flex;align-items:center;gap:10px;
}
.form-panel-title svg{width:20px;height:20px;color:var(--accent)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
 
/* Table */
.table-panel{
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--radius-lg);overflow:hidden;
}
.table-panel-header{
  padding:20px 24px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
}
.table-panel-title{font-family:var(--font-head);font-weight:700;font-size:1rem}
table{width:100%;border-collapse:collapse}
thead th{
  padding:12px 20px;text-align:left;
  font-size:.78rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;
  color:var(--text-dim);border-bottom:1px solid var(--border);
  background:var(--bg2);
}
tbody tr{border-bottom:1px solid var(--border);transition:.15s}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:rgba(0,212,255,.03)}
tbody td{padding:16px 20px;font-size:.88rem;vertical-align:middle}
.td-icon{
  width:36px;height:36px;
  background:rgba(0,212,255,.08);border:1px solid rgba(0,212,255,.15);
  border-radius:8px;display:flex;align-items:center;justify-content:center;
  color:var(--accent);flex-shrink:0;
}
.td-icon svg{width:16px;height:16px}
.td-title{font-weight:500}
.td-desc{color:var(--text-muted);font-size:.82rem;margin-top:2px;max-width:300px;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.td-tag{
  display:inline-block;
  background:rgba(123,97,255,.12);border:1px solid rgba(123,97,255,.2);
  color:var(--accent2);font-size:.72rem;font-weight:600;
  padding:2px 10px;border-radius:100px;
}
.td-actions{display:flex;gap:8px;align-items:center}
.no-data{padding:48px;text-align:center;color:var(--text-dim)}
 
/* ═══════════════════════════════════════════════════════
   ANIMACIONES
═══════════════════════════════════════════════════════ */
@keyframes fadeUp{
  from{opacity:0;transform:translateY(20px)}
  to{opacity:1;transform:translateY(0)}
}
.animate{animation:fadeUp .5s ease both}
 
/* Responsive */
@media(max-width:900px){
  .panel-layout{grid-template-columns:1fr}
  .sidebar{display:none}
  .panel-main{padding:24px}
  .form-row{grid-template-columns:1fr}
  .stats-row{grid-template-columns:1fr}
}
@media(max-width:600px){
  .hero-stats{gap:24px}
  .services-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>
 
<?php if ($view === 'public'): ?>
<!-- ═══════════════════════════════════════════════════════
     VISTA PÚBLICA
═══════════════════════════════════════════════════════ -->
<nav class="navbar">
  <div class="container">
    <div class="nav-logo">AX<span>soft</span></div>
    <div class="nav-links">
      <a href="#services" class="btn btn-secondary btn-sm">Servicios</a>
      <a href="#contact" class="btn btn-secondary btn-sm">Contacto</a>
      <a href="?panel" class="btn btn-primary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        Admin
      </a>
    </div>
  </div>
</nav>
 
<section class="hero">
  <div class="hero-grid"></div>
  <div class="container">
    <div class="hero-content">
      <div class="hero-badge">✦ Soluciones tecnológicas de vanguardia</div>
      <h1>Software que <em>transforma</em> tu negocio</h1>
      <p>En AXSoft diseñamos y desarrollamos soluciones digitales a medida — desde aplicaciones web y móviles hasta sistemas de inteligencia artificial y arquitecturas cloud.</p>
      <div class="hero-cta">
        <a href="#services" class="btn btn-primary">Ver servicios</a>
        <a href="#contact" class="btn btn-secondary">Hablar con un experto</a>
      </div>
      <div class="hero-stats">
        <div class="stat-item">
          <div class="stat-num"><?= count($services) ?></div>
          <div class="stat-label">Servicios activos</div>
        </div>
        <div class="stat-item">
          <div class="stat-num">8+</div>
          <div class="stat-label">Años de experiencia</div>
        </div>
        <div class="stat-item">
          <div class="stat-num">200+</div>
          <div class="stat-label">Proyectos entregados</div>
        </div>
      </div>
    </div>
  </div>
</section>
 
<section class="section" id="services">
  <div class="container">
    <div class="section-header">
      <div class="section-tag">Portafolio</div>
      <h2 class="section-title">Nuestros Servicios</h2>
      <p class="section-sub">Soluciones integrales adaptadas a las necesidades de tu empresa.</p>
    </div>
    <div class="services-grid">
      <?php if (empty($services)): ?>
        <div class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
            <rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>
          </svg>
          <p>Aún no hay servicios publicados.</p>
        </div>
      <?php else: foreach ($services as $i => $s): ?>
        <div class="service-card" style="animation:fadeUp .5s <?= $i * .08 ?>s ease both">
          <?php if ($s['tag']): ?>
            <span class="card-tag"><?= e($s['tag']) ?></span>
          <?php endif; ?>
          <div class="card-icon"><?= getIcon($s['icon']) ?></div>
          <h3 class="card-title"><?= e($s['title']) ?></h3>
          <p class="card-desc"><?= nl2br(e($s['description'])) ?></p>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</section>
 
<section class="cta-section" id="contact">
  <div class="container">
    <h2>¿Listo para empezar?</h2>
    <p>Cuéntanos tu proyecto y te ayudamos a llevarlo al siguiente nivel.</p>
    <a href="mailto:contacto@axsoft.mx" class="btn btn-primary">contacto@axsoft.mx</a>
  </div>
</section>
 
<footer>
  <div class="container">
    <p>© <?= date('Y') ?> <strong>AXSoft</strong> — Todos los derechos reservados.</p>
  </div>
</footer>
 
<?php elseif ($view === 'login'): ?>
<!-- ═══════════════════════════════════════════════════════
     LOGIN
═══════════════════════════════════════════════════════ -->
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">AX<span>soft</span></div>
    <div class="login-sub">Panel de administración</div>
    <?php if ($error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="?panel">
      <input type="hidden" name="action" value="login">
      <div class="form-group">
        <label for="password">Contraseña de acceso</label>
        <input type="password" id="password" name="password" placeholder="••••••••" autofocus required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
        Ingresar al panel
      </button>
    </form>
    <div style="text-align:center;margin-top:20px">
      <a href="?" style="color:var(--text-muted);font-size:.85rem">← Volver al sitio</a>
    </div>
  </div>
</div>
 
<?php elseif ($view === 'panel'): ?>
<!-- ═══════════════════════════════════════════════════════
     PANEL ADMIN
═══════════════════════════════════════════════════════ -->
<div class="panel-layout">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-logo">AX<span>soft</span></div>
    <div class="sidebar-role">Panel de administración</div>
    <ul class="sidebar-nav">
      <li>
        <a href="?panel" class="active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
          Dashboard
        </a>
      </li>
      <li>
        <a href="?" target="_blank">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/></svg>
          Ver sitio público
        </a>
      </li>
    </ul>
    <div class="sidebar-divider"></div>
    <form method="POST">
      <input type="hidden" name="action" value="logout">
      <button type="submit" style="width:100%;text-align:left;background:none;border:none;cursor:pointer;" class="btn btn-secondary btn-sm">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Cerrar sesión
      </button>
    </form>
  </aside>
 
  <!-- Main -->
  <main class="panel-main">
    <div class="panel-header">
      <div>
        <div class="panel-title">Gestión de Servicios</div>
        <div class="panel-subtitle">Agrega, edita o elimina los servicios que aparecen en el sitio público.</div>
      </div>
    </div>
 
    <?php if ($error): ?>
      <div class="alert alert-error animate"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success animate"><?= e($success) ?></div>
    <?php endif; ?>
 
    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card animate">
        <div class="num"><?= count($services) ?></div>
        <div class="lbl">Servicios publicados</div>
      </div>
      <div class="stat-card animate" style="animation-delay:.05s">
        <div class="num"><?= count(array_unique(array_column($services,'icon'))) ?></div>
        <div class="lbl">Categorías usadas</div>
      </div>
      <div class="stat-card animate" style="animation-delay:.1s">
        <div class="num"><?= date('d/m/Y') ?></div>
        <div class="lbl">Última sesión</div>
      </div>
    </div>
 
    <!-- Formulario -->
    <div class="form-panel animate" style="animation-delay:.15s">
      <div class="form-panel-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><?= $editService ? '<path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>' : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>' ?></svg>
        <?= $editService ? 'Editar servicio #'.$editService['id'] : 'Agregar nuevo servicio' ?>
      </div>
      <form method="POST" action="?panel<?= $editService ? '&edit='.$editService['id'] : '' ?>">
        <input type="hidden" name="action" value="<?= $editService ? 'edit' : 'add' ?>">
        <?php if ($editService): ?>
          <input type="hidden" name="id" value="<?= $editService['id'] ?>">
        <?php endif; ?>
        <div class="form-row">
          <div class="form-group">
            <label>Nombre del servicio *</label>
            <input type="text" name="title" placeholder="Ej: Desarrollo Web" maxlength="80"
              value="<?= $editService ? e($editService['title']) : '' ?>" required>
          </div>
          <div class="form-group">
            <label>Etiqueta (opcional)</label>
            <input type="text" name="tag" placeholder="Ej: Nuevo, Popular" maxlength="20"
              value="<?= $editService ? e($editService['tag']) : '' ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Ícono</label>
            <select name="icon">
              <?php
              $icons = ['web'=>'🌐 Web','mobile'=>'📱 Mobile','cloud'=>'☁️ Cloud','security'=>'🔒 Seguridad','data'=>'🗄️ Datos','ai'=>'🤖 Inteligencia Artificial','support'=>'💬 Soporte','design'=>'🎨 Diseño'];
              foreach ($icons as $k => $label):
                $sel = ($editService && $editService['icon'] === $k) ? 'selected' : '';
              ?>
                <option value="<?= $k ?>" <?= $sel ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Descripción *</label>
          <textarea name="description" placeholder="Describe el servicio en detalle..." required><?= $editService ? e($editService['description']) : '' ?></textarea>
        </div>
        <div style="display:flex;gap:12px;align-items:center">
          <button type="submit" class="btn btn-primary">
            <?= $editService ? 'Guardar cambios' : 'Agregar servicio' ?>
          </button>
          <?php if ($editService): ?>
            <a href="?panel" class="btn btn-secondary">Cancelar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
 
    <!-- Tabla de servicios -->
    <div class="table-panel animate" style="animation-delay:.2s">
      <div class="table-panel-header">
        <div class="table-panel-title">Servicios publicados</div>
        <span style="color:var(--text-muted);font-size:.85rem"><?= count($services) ?> servicio<?= count($services) !== 1 ? 's' : '' ?></span>
      </div>
      <?php if (empty($services)): ?>
        <div class="no-data">No hay servicios aún. Agrega el primero arriba.</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Ícono</th>
              <th>Servicio</th>
              <th>Etiqueta</th>
              <th>Creado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($services as $s): ?>
            <tr>
              <td>
                <div class="td-icon"><?= getIcon($s['icon']) ?></div>
              </td>
              <td>
                <div class="td-title"><?= e($s['title']) ?></div>
                <div class="td-desc"><?= e($s['description']) ?></div>
              </td>
              <td>
                <?php if ($s['tag']): ?>
                  <span class="td-tag"><?= e($s['tag']) ?></span>
                <?php else: ?>
                  <span style="color:var(--text-dim)">—</span>
                <?php endif; ?>
              </td>
              <td style="color:var(--text-muted);font-size:.82rem">
                <?= isset($s['created_at']) ? date('d/m/Y', strtotime($s['created_at'])) : '—' ?>
              </td>
              <td>
                <div class="td-actions">
                  <a href="?panel&edit=<?= $s['id'] ?>" class="btn btn-secondary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4z"/></svg>
                    Editar
                  </a>
                  <form method="POST" onsubmit="return confirm('¿Eliminar «<?= e($s['title']) ?>»?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                      Eliminar
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php endif; ?>
 
</body>
</html>
 