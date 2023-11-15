-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: ひみつ
-- 生成日時: 2023-11-15 14:03:33
-- サーバのバージョン： 10.4.28-MariaDB
-- PHP のバージョン: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- データベース: `account`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `account`
--

CREATE TABLE `account` (
  `sysid` int(11) NOT NULL,
  `username` varchar(500) NOT NULL,
  `userid` varchar(500) NOT NULL,
  `password` varchar(1024) NOT NULL,
  `loginid` varchar(256) NOT NULL,
  `token` varchar(256) NOT NULL,
  `mailadds` varchar(500) NOT NULL,
  `profile` text NOT NULL,
  `iconname` varchar(256) NOT NULL,
  `headname` varchar(256) NOT NULL,
  `role` varchar(1024) NOT NULL,
  `datetime` datetime NOT NULL,
  `follow` text NOT NULL,
  `follower` text NOT NULL,
  `blocklist` text NOT NULL,
  `admin` varchar(50) NOT NULL,
  `authcode` varchar(256) NOT NULL,
  `backupcode` varchar(256) NOT NULL,
  `sacinfo` varchar(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `ads`
--

CREATE TABLE `ads` (
  `sysid` int(11) NOT NULL,
  `uniqid` varchar(512) NOT NULL,
  `url` varchar(512) NOT NULL,
  `image_url` varchar(512) NOT NULL,
  `memo` text NOT NULL,
  `start_date` datetime NOT NULL,
  `limit_date` datetime NOT NULL,
  `datetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `emoji`
--

CREATE TABLE `emoji` (
  `sysid` int(11) NOT NULL,
  `emojifile` varchar(512) NOT NULL,
  `emojitype` varchar(256) NOT NULL,
  `emojicontent` mediumblob NOT NULL,
  `emojisize` int(11) NOT NULL,
  `emojiname` varchar(512) NOT NULL,
  `emojiinfo` text NOT NULL,
  `emojidate` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `invitation`
--

CREATE TABLE `invitation` (
  `sysid` int(11) NOT NULL,
  `code` varchar(512) NOT NULL,
  `used` varchar(25) NOT NULL,
  `datetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `notice`
--

CREATE TABLE `notice` (
  `sysid` int(11) NOT NULL,
  `title` varchar(1024) NOT NULL,
  `note` text NOT NULL,
  `account` varchar(256) NOT NULL,
  `datetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `notification`
--

CREATE TABLE `notification` (
  `sysid` int(11) NOT NULL,
  `touserid` varchar(500) NOT NULL,
  `msg` text NOT NULL,
  `url` varchar(512) NOT NULL,
  `datetime` datetime NOT NULL,
  `userchk` varchar(25) NOT NULL,
  `title` varchar(1024) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `report`
--

CREATE TABLE `report` (
  `sysid` int(11) NOT NULL,
  `uniqid` varchar(256) NOT NULL,
  `userid` varchar(500) NOT NULL,
  `report_userid` varchar(500) NOT NULL,
  `msg` text NOT NULL,
  `datetime` datetime NOT NULL,
  `admin_chk` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `role`
--

CREATE TABLE `role` (
  `sysid` int(11) NOT NULL,
  `rolename` varchar(512) NOT NULL,
  `roleauth` varchar(256) NOT NULL,
  `rolecolor` varchar(25) NOT NULL,
  `roleidname` varchar(512) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `ueuse`
--

CREATE TABLE `ueuse` (
  `sysid` int(11) NOT NULL,
  `username` varchar(512) NOT NULL,
  `account` varchar(256) NOT NULL,
  `uniqid` varchar(256) NOT NULL,
  `rpuniqid` varchar(256) NOT NULL,
  `ueuse` text NOT NULL,
  `photo1` varchar(512) NOT NULL,
  `photo2` varchar(512) NOT NULL,
  `photo3` varchar(512) NOT NULL,
  `photo4` varchar(512) NOT NULL,
  `video1` varchar(512) NOT NULL,
  `datetime` datetime NOT NULL,
  `favorite` text NOT NULL,
  `abi` text NOT NULL,
  `abidate` datetime NOT NULL,
  `nsfw` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`sysid`);

--
-- テーブルのインデックス `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`sysid`);

--
-- テーブルのインデックス `emoji`
--
ALTER TABLE `emoji`
  ADD PRIMARY KEY (`sysid`);

--
-- テーブルのインデックス `invitation`
--
ALTER TABLE `invitation`
  ADD PRIMARY KEY (`sysid`);

--
-- テーブルのインデックス `notice`
--
ALTER TABLE `notice`
  ADD PRIMARY KEY (`sysid`);

--
-- テーブルのインデックス `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`sysid`);

--
-- テーブルのインデックス `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`sysid`);

--
-- テーブルのインデックス `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`sysid`);

--
-- テーブルのインデックス `ueuse`
--
ALTER TABLE `ueuse`
  ADD PRIMARY KEY (`sysid`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `account`
--
ALTER TABLE `account`
  MODIFY `sysid` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `ads`
--
ALTER TABLE `ads`
  MODIFY `sysid` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `emoji`
--
ALTER TABLE `emoji`
  MODIFY `sysid` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `invitation`
--
ALTER TABLE `invitation`
  MODIFY `sysid` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `notice`
--
ALTER TABLE `notice`
  MODIFY `sysid` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `notification`
--
ALTER TABLE `notification`
  MODIFY `sysid` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `report`
--
ALTER TABLE `report`
  MODIFY `sysid` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `role`
--
ALTER TABLE `role`
  MODIFY `sysid` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `ueuse`
--
ALTER TABLE `ueuse`
  MODIFY `sysid` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
