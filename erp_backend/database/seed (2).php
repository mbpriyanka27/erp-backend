<?php
/**
 * Run from the command line after importing schema.sql:
 *   php database/seed.php
 *
 * Passwords are hashed here with PHP's password_hash() at insert time,
 * rather than pasted as static SQL, so they're guaranteed to verify
 * correctly with password_verify() in login.php.
 *
 * Demo password for every seeded user: Password@123
 */

require_once __DIR__ . '/../config/database.php';

$pdo = get_db_connection();
$demoPassword = password_hash('Password@123', PASSWORD_DEFAULT);

echo "Seeding roles...\n";
$roles = ['Student', 'Faculty', 'Coordinator', 'HOD', 'Principal', 'Director', 'VC', 'Admin'];
$roleIds = [];
$stmt = $pdo->prepare('INSERT INTO roles (name) VALUES (:name)');
foreach ($roles as $roleName) {
    $stmt->execute(['name' => $roleName]);
    $roleIds[$roleName] = (int) $pdo->lastInsertId();
}

echo "Seeding departments...\n";
$departments = [
    ['name' => 'Computer Science & Engineering', 'code' => 'CSE'],
    ['name' => 'Electronics & Communication', 'code' => 'ECE'],
    ['name' => 'Mechanical Engineering', 'code' => 'MECH'],
];
$deptIds = [];
$stmt = $pdo->prepare('INSERT INTO departments (name, code) VALUES (:name, :code)');
foreach ($departments as $dept) {
    $stmt->execute($dept);
    $deptIds[$dept['code']] = (int) $pdo->lastInsertId();
}

echo "Seeding event categories...\n";
$categories = [
    ['name' => 'Workshop', 'level' => 'department'],
    ['name' => 'Seminar', 'level' => 'department'],
    ['name' => 'Club Activity', 'level' => 'department'],
    ['name' => 'Technical Event', 'level' => 'department'],
    ['name' => 'Department Fest', 'level' => 'department'],
    ['name' => 'Cultural Fest', 'level' => 'university'],
    ['name' => 'Sports Meet', 'level' => 'university'],
    ['name' => 'Convocation', 'level' => 'university'],
    ['name' => 'National Conference', 'level' => 'university'],
    ['name' => 'University Workshop', 'level' => 'university'],
];
$stmt = $pdo->prepare('INSERT INTO event_categories (name, level) VALUES (:name, :level)');
foreach ($categories as $cat) {
    $stmt->execute($cat);
}

echo "Seeding demo users (all roles, CSE department where applicable)...\n";
$users = [
    ['name' => 'Asha Rao',      'email' => 'student@erp.test',      'role' => 'Student'],
    ['name' => 'Dr. Kiran Shah','email' => 'faculty@erp.test',      'role' => 'Faculty'],
    ['name' => 'Meera Iyer',    'email' => 'coordinator@erp.test',  'role' => 'Coordinator'],
    ['name' => 'Prof. R. Nair', 'email' => 'hod@erp.test',          'role' => 'HOD'],
    ['name' => 'Dr. S. Menon',  'email' => 'principal@erp.test',    'role' => 'Principal'],
    ['name' => 'Dr. A. Verma',  'email' => 'director@erp.test',     'role' => 'Director'],
    ['name' => 'Dr. P. Krishnan','email' => 'vc@erp.test',          'role' => 'VC'],
    ['name' => 'System Admin',  'email' => 'admin@erp.test',        'role' => 'Admin'],
];
$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash, role_id, department_id)
     VALUES (:name, :email, :password_hash, :role_id, :department_id)'
);
foreach ($users as $u) {
    // Director, VC, Admin sit above department level -> no department_id
    $deptId = in_array($u['role'], ['Director', 'VC', 'Admin'], true) ? null : $deptIds['CSE'];
    $stmt->execute([
        'name' => $u['name'],
        'email' => $u['email'],
        'password_hash' => $demoPassword,
        'role_id' => $roleIds[$u['role']],
        'department_id' => $deptId,
    ]);
}

echo "Done. All demo users share the password: Password@123\n";
