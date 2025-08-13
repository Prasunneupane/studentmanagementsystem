-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 08, 2019 at 06:48 AM
-- Server version: 10.1.35-MariaDB
-- PHP Version: 7.2.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `onlinezeal_ombuddha`
--

-- --------------------------------------------------------

--
-- Table structure for table `districts`
--

CREATE TABLE `districts` (
  `id` int(10) UNSIGNED NOT NULL,
  `province_id` int(11) DEFAULT NULL,
  `district_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `districts`
--'name'=>

istricts`[`d now(), now()'name'=>]plejung',1, now(), now()],
[ 'name'=>'Panchthar',1, now(), now()],
[ 'name'=>'Ilam',1, now(), now()],
[ 'name'=>'Jhapa',1, now(), now()],
[ 'name'=>'Morang',1, now(), now()],
[ 'name'=>'Sunasari',1, now(), now()],
[ 'name'=>'Dhankuta',1, now(), now()],
[ 'name'=>'Terhathum',1, now(), now()],
[ 'name'=>'Sankhusabha',1, now(), now()],
[ 'name'=>'Bhojpur',1, now(), now()],
[ 'name'=>'Solukhumbu',1, now(), now()],
[ 'name'=>'Okhaldunga',1, now(), now()],
[ 'name'=>'Khotang',1, now(), now()],
[ 'name'=>'Udayapur',1, now(), now()],
[ 'name'=>'Saptari',2, now(), now()],
[ 'name'=>'Siraha',2, now(), now()],
[ 'name'=>'Dhanusha',2, now(), now()],
[ 'name'=>'Mahottari',2, now(), now()],
[ 'name'=>'Sarlahi',2, now(), now()],
[ 'name'=>'Rautahat',2, now(), now()],
[ 'name'=>'Bara',2, now(), now()],
[ 'name'=>'Parsa',2, now(), now()],
[ 'name'=>'Sindhuli',3, now(), now()],
[ 'name'=>'Ramechhap',3, now(), now()],
[ 'name'=>'Dolakha',3, now(), now()],
[ 'name'=>'Sindhupalchowk',3, now(), now()],
[ 'name'=>'Kavrepalanchok',3, now(), now()],
[ 'name'=>'Lalitpur',3, now(), now()],
[ 'name'=>'Bhaktapur',3, now(), now()],
[ 'name'=>'Kathmandu',3, now(), now()],
[ 'name'=>'Nuwakot',3, now(), now()],
[ 'name'=>'Rasuwa',3, now(), now()],
[ 'name'=>'Dhading',3, now(), now()],
[ 'name'=>'Makawanpur',3, now(), now()],
[ 'name'=>'Chitwan',3, now(), now()],
[ 'name'=>'Gorkha',4, now(), now()],
[ 'name'=>'Lamjung',4, now(), now()],
[ 'name'=>'Tanahun',4, now(), now()],
[ 'name'=>'Syangja',4, now(), now()],
[ 'name'=>'Kaski',4, now(), now()],
[ 'name'=>'Manang',4, now(), now()],
[ 'name'=>'Mustang',4, now(), now()],
[ 'name'=>'Myagdi',4, now(), now()],
[ 'name'=>'Parbat',4, now(), now()],
[ 'name'=>'Baglung',4, now(), now()],
[ 'name'=>'Nawalparasi',4, now(), now()],
[ 'name'=>'Gulmi',5, now(), now()],
[ 'name'=>'Palpa',5, now(), now()],
[ 'name'=>'Rupandehi',5, now(), now()],
[ 'name'=>'Kapilbastu',5, now(), now()],
[ 'name'=>'Arghakhanchi',5, now(), now()],
[ 'name'=>'Pyuthan',5, now(), now()],
[ 'name'=>'Rolpa',5, now(), now()],
[ 'name'=>'Rukum',5, now(), now()],
[ 'name'=>'Dang',5, now(), now()],
[ 'name'=>'Banke',5, now(), now()],
[ 'name'=>'Bardiya',5, now(), now()],
[ 'name'=>'Salyan',6, now(), now()],
[ 'name'=>'Surkhet',6, now(), now()],
[ 'name'=>'Dailekh',6, now(), now()],
[ 'name'=>'Jajarkot',6, now(), now()],
[ 'name'=>'Dolpa',6, now(), now()],
[ 'name'=>'Jumla',6, now(), now()],
[ 'name'=>'Kalikot',6, now(), now()],
[ 'name'=>'Mugu',6, now(), now()],
[ 'name'=>'Humla',6, now(), now()],
[ 'name'=>'Bajura',7, now(), now()],
[ 'name'=>'Bajhang',7, now(), now()],
[ 'name'=>'Achham',7, now(), now()],
[ 'name'=>'Doti',7, now(), now()],
[ 'name'=>'Kailali',7, now(), now()],
[ 'name'=>'Kanchanpur',7, now(), now()],
[ 'name'=>'Dadeldhura',7, now(), now()],
[ 'name'=>'Baitadi',7, now(), now()],
[ 'name'=>'Darchula',7, now(), now()];
r 'dumpedname'=> tables[fo
now()T now()]BLE `districts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
