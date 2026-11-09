-- Database: Etalase Digital Stella (XI RPL - CRUD + Hak Akses)
-- Import file ini lewat phpMyAdmin (database lama akan dihapus lalu dibuat ulang).
DROP DATABASE IF EXISTS db_etalase_stella;
CREATE DATABASE db_etalase_stella CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE db_etalase_stella;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('super_admin','admin','siswa') NOT NULL DEFAULT 'siswa',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  judul VARCHAR(150) NOT NULL,
  deskripsi TEXT NOT NULL,
  gambar VARCHAR(255) DEFAULT NULL,
  link_pendukung VARCHAR(255) NOT NULL,
  link_demo VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_projects_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Akun awal (password di-hash; login: superadmin/superadmin123, admin/admin123, siswa/siswa123)
INSERT IGNORE INTO users (nama, username, password, role) VALUES
('Super Admin', 'superadmin', '$6$L79v0Q.EeusWrD6v$AGIYcMMt21z5BuTAGSRsdAczxDudITcADagurbHsteSd0UeEYtxU34L3cRfULKr9RBkEqmJ52x.QzJSZdEyCK/', 'super_admin'),
('Admin Stella', 'admin', '$6$Q1NBNaCgyikPtpY3$SsVob2AnQxMB81FryEeirzTaqkogiBz/L4UcbDcjegWew7XMDYNQuQpOxjvtIZpaKuZGwhE6lj8kXVokfty2a1', 'admin'),
('Siswa Contoh', 'siswa', '$6$I.Fcx3ClZ/GtiNyl$/Eo61B9GmY3xNUIOixFegEGXAiBbLQ6aCg4SWmfgH3mQuNUGYiTk8dIBjaAYLSITvYMJbyHEcR9kfYeetHzfl/', 'siswa');
