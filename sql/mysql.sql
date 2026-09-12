-- KURO Panel schema (MySQL 5.7+/MariaDB). Use only if DB_DRIVER=mysql.
-- Default install uses SQLite (sql/sqlite.sql, auto-created). No admin row here:
-- create it with a bcrypt(md5('XquxmymXDtWRA66D'+password)) hash (see README).
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
/*!40101 SET NAMES utf8mb4 */;

CREATE TABLE `credit` (
  `id` int(11) NOT NULL,
  `name` varchar(10) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `credit` (`id`, `name`) VALUES (1, '');

CREATE TABLE `Feature` (
  `id` int(11) NOT NULL,
  `ESP` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `Item` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `SilentAim` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `AIM` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `BulletTrack` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `Memory` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `Floating` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `Setting` varchar(3) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `Feature` (`id`, `ESP`, `Item`, `SilentAim`, `AIM`, `BulletTrack`, `Memory`, `Floating`, `Setting`) VALUES
(1, 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on');

CREATE TABLE `history` (
  `id_history` int(11) NOT NULL,
  `keys_id` varchar(33) DEFAULT NULL,
  `user_do` varchar(33) DEFAULT NULL,
  `info` mediumtext NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `keys_code` (
  `id_keys` int(11) NOT NULL,
  `game` varchar(32) NOT NULL,
  `user_key` varchar(32) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `expired_date` datetime DEFAULT NULL,
  `max_devices` int(11) DEFAULT NULL,
  `devices` mediumtext,
  `status` tinyint(1) DEFAULT '1',
  `registrator` varchar(32) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `lib` (
  `id` int(11) NOT NULL,
  `file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `file_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `file_size` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `modname` (
  `id` int(11) NOT NULL,
  `modname` varchar(100) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `modname` (`id`, `modname`) VALUES (1, 'VIP MOD');

CREATE TABLE `onoff` (
  `id` int(11) NOT NULL,
  `status` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `myinput` varchar(500) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `onoff` (`id`, `status`, `myinput`) VALUES (1, 'off', '');

CREATE TABLE `referral_code` (
  `id_reff` int(11) NOT NULL,
  `code` varchar(128) NOT NULL,
  `Referral` varchar(7) NOT NULL,
  `level` int(11) NOT NULL,
  `set_saldo` int(11) NOT NULL DEFAULT '0',
  `used_by` varchar(66) NOT NULL,
  `created_by` varchar(66) NOT NULL DEFAULT 'Owner',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `acc_expiration` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `users` (
  `id_users` int(11) NOT NULL,
  `fullname` varchar(155) DEFAULT NULL,
  `username` varchar(66) NOT NULL,
  `email` varchar(40) NOT NULL,
  `reset_link_token` varchar(255) NOT NULL,
  `exp_date` varchar(250) NOT NULL,
  `level` int(11) NOT NULL,
  `saldo` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `uplink` varchar(66) DEFAULT NULL,
  `password` varchar(155) NOT NULL,
  `user_ip` varchar(155) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `expiration_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `_ftext` (
  `id` int(11) NOT NULL,
  `_status` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `_ftext` varchar(100) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `_ftext` (`id`, `_status`, `_ftext`) VALUES (1, 'Safe', 'MOD STATUS :- 100% SAFE');

ALTER TABLE `credit` ADD PRIMARY KEY (`id`);
ALTER TABLE `Feature` ADD PRIMARY KEY (`id`);
ALTER TABLE `history` ADD PRIMARY KEY (`id_history`);
ALTER TABLE `keys_code` ADD PRIMARY KEY (`id_keys`), ADD UNIQUE KEY `user_key` (`user_key`);
ALTER TABLE `lib` ADD PRIMARY KEY (`id`);
ALTER TABLE `modname` ADD PRIMARY KEY (`id`);
ALTER TABLE `onoff` ADD PRIMARY KEY (`id`);
ALTER TABLE `referral_code` ADD PRIMARY KEY (`id_reff`);
ALTER TABLE `users` ADD PRIMARY KEY (`id_users`), ADD UNIQUE KEY `username` (`username`,`email`);
ALTER TABLE `_ftext` ADD PRIMARY KEY (`id`);

ALTER TABLE `credit` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `Feature` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `history` MODIFY `id_history` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `keys_code` MODIFY `id_keys` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `lib` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `modname` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `onoff` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `referral_code` MODIFY `id_reff` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` MODIFY `id_users` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `_ftext` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;
