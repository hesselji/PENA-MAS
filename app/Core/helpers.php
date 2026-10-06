<?php
use App\Core\Auth;
use App\Core\Csrf;

function e(?string $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function csrf_field(): string { return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">'; }
function auth_user(): ?array { return Auth::user(); }
function old(string $key, string $default = ''): string { return e($_SESSION['old'][$key] ?? $default); }
function flash(string $key): ?string { $v = $_SESSION[$key] ?? null; unset($_SESSION[$key]); return $v; }
function format_date_id(?string $value, bool $withTime = false): string {
    if (!$value) return '-';
    $ts = strtotime($value);
    $months = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
    $base = date('d', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    return $withTime ? $base . ', ' . date('H:i', $ts) . ' WIB' : $base;
}
function month_year_id(?string $value = null): string {
    $ts = $value ? strtotime($value) : time();
    $months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    return $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}
function sensitivity_label(string $code): string {
    return match ($code) { 'BIASA' => 'Biasa', 'PENTING' => 'Penting', 'RAHASIA' => 'Rahasia', 'SANGAT_RAHASIA' => 'Sangat Rahasia', default => $code };
}
function incoming_status_label(string $status): string {
    return match ($status) { 'BARU' => 'Baru', 'DIPROSES' => 'Diproses', 'SELESAI' => 'Selesai', default => ucfirst(strtolower($status)) };
}
function status_label(string $status): string {
    return match ($status) { 'ACTIVE' => 'Aktif', 'CANCELLED' => 'Dibatalkan', default => ucfirst(strtolower($status)) };
}
function current_path(): string { return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'; }
function nav_active(string $prefix, bool $exact = false): string {
    $path = current_path();
    $active = $exact ? $path === $prefix : str_starts_with($path, $prefix);
    return $active ? ' active' : '';
}

function display_user_name(?string $name): string {
    $clean = trim((string)$name);
    $clean = preg_replace('/\s+Prototype$/iu', '', $clean) ?? $clean;
    if (strcasecmp($clean, 'Admin') === 0) return 'Administrator';
    if (strcasecmp($clean, 'User') === 0) return 'Pengguna';
    return $clean !== '' ? $clean : 'Pengguna';
}

function user_initials(?string $name): string {
    $parts = preg_split('/\s+/', trim((string)$name)) ?: [];
    $parts = array_values(array_filter($parts));
    if (!$parts) return 'U';
    $first = mb_substr($parts[0], 0, 1);
    $last = count($parts) > 1 ? mb_substr($parts[count($parts)-1], 0, 1) : '';
    return mb_strtoupper($first . $last);
}
function ui_icon(string $name, string $class = ''): string {
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
        'mail-plus' => '<path d="M4 4h10a2 2 0 0 1 2 2v8"/><path d="m4 6 6 5 6-5"/><path d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h9"/><path d="M19 14v6"/><path d="M16 17h6"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 .6 1.65 1.65 0 0 0-.38 1.08V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1.08-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6h.02A1.65 1.65 0 0 0 10 3.09V3a2 2 0 1 1 4 0v.09A1.65 1.65 0 0 0 15 4.6a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9v.02A1.65 1.65 0 0 0 20.91 10H21a2 2 0 1 1 0 4h-.09A1.65 1.65 0 0 0 19.4 15Z"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'x' => '<path d="m18 6-12 12M6 6l12 12"/>',
        'logout' => '<path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'filter' => '<path d="M4 5h16l-6 7v5l-4 2v-7Z"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'ban' => '<circle cx="12" cy="12" r="9"/><path d="m5.7 5.7 12.6 12.6"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/>',
        'chart' => '<path d="M4 19V9M10 19V5M16 19v-7M22 19V2"/>',
        'archive' => '<rect x="3" y="4" width="18" height="5" rx="1"/><path d="M5 9v11h14V9M9 13h6"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
        'copy' => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'moon' => '<path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/>',
        'database' => '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.66 3.58 3 8 3s8-1.34 8-3V5"/><path d="M4 11v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6"/>',
        'refresh' => '<path d="M20 11a8 8 0 1 0 1 5"/><path d="M20 4v7h-7"/>',
        'download' => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
        'printer' => '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/>',
        'signature' => '<path d="M3 17c3.5-4 5.5-6 7-6 2.2 0 1.4 4.4 3.2 4.4 1.4 0 2.6-2.4 4.8-2.4 1.2 0 2 .5 3 1.5"/><path d="M4 21h16"/>',
        'template' => '<path d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M8 7h8M8 11h8M8 15h5"/>',
        'history' => '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v6h6"/><path d="M12 7v5l3 2"/>',
        'workflow' => '<circle cx="6" cy="6" r="2"/><circle cx="18" cy="12" r="2"/><circle cx="6" cy="18" r="2"/><path d="M8 6h4a4 4 0 0 1 4 4M16 14a4 4 0 0 1-4 4H8"/>',
        'upload' => '<path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M5 20h14"/>',
        'layers' => '<path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
    ];
    $body = $icons[$name] ?? $icons['info'];
    return '<svg class="ui-icon ' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}
