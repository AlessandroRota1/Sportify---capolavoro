-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 18, 2025 alle 19:59
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sportify`
--
CREATE DATABASE IF NOT EXISTS `sportify` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `sportify`;

-- --------------------------------------------------------

--
-- Struttura della tabella `amicizie`
--

DROP TABLE IF EXISTS `amicizie`;
CREATE TABLE `amicizie` (
  `id` int(11) NOT NULL,
  `id_mittente` int(11) NOT NULL,
  `id_destinatario` int(11) NOT NULL,
  `stato` enum('in_attesa','accettata','rifiutata') DEFAULT 'in_attesa',
  `data_richiesta` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `amicizie`
--

INSERT INTO `amicizie` (`id`, `id_mittente`, `id_destinatario`, `stato`, `data_richiesta`) VALUES
(1, 1, 2, 'accettata', '2025-05-06 00:00:00'),
(2, 2, 3, 'accettata', '2025-05-06 00:00:00'),
(3, 1, 3, 'accettata', '2025-05-10 00:00:00'),
(4, 1, 6, 'accettata', '2025-05-10 00:00:00'),
(5, 1, 7, 'accettata', '2025-05-10 00:00:00');

-- --------------------------------------------------------

--
-- Struttura della tabella `calcetti`
--

DROP TABLE IF EXISTS `calcetti`;
CREATE TABLE `calcetti` (
  `id_calcetto` int(11) NOT NULL,
  `data_ora` datetime NOT NULL,
  `posti_occupati` int(11) NOT NULL,
  `visibilita` tinyint(1) NOT NULL,
  `id_campo` int(11) NOT NULL,
  `id_utente` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `calcetti`
--

INSERT INTO `calcetti` (`id_calcetto`, `data_ora`, `posti_occupati`, `visibilita`, `id_campo`, `id_utente`) VALUES
(1, '2025-04-09 19:00:00', 11, 1, 4, 1),
(2, '2025-04-09 21:00:00', 7, 1, 4, 1),
(3, '2025-04-20 13:00:00', 1, 0, 4, 2),
(4, '2025-04-10 19:00:00', 6, 1, 4, 1),
(5, '2025-04-11 19:00:00', 3, 1, 5, 1),
(6, '2025-04-10 02:00:00', 4, 1, 7, 4),
(7, '2025-10-05 23:00:00', 2, 1, 12, 5),
(8, '2025-05-10 19:00:00', 1, 0, 12, 1),
(9, '2025-06-12 10:30:00', 1, 0, 12, 1),
(10, '2025-05-25 10:00:00', 1, 0, 12, 3),
(11, '2025-06-02 10:00:00', 1, 0, 10, 1),
(12, '2025-06-03 10:00:00', 2, 0, 4, 7),
(13, '2025-06-03 10:00:00', 2, 1, 4, 6);

-- --------------------------------------------------------

--
-- Struttura della tabella `calcetto_utente`
--

DROP TABLE IF EXISTS `calcetto_utente`;
CREATE TABLE `calcetto_utente` (
  `Id_calcettoutente` int(11) NOT NULL,
  `Id_utente` int(11) NOT NULL,
  `Id_calcetto` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `calcetto_utente`
--

INSERT INTO `calcetto_utente` (`Id_calcettoutente`, `Id_utente`, `Id_calcetto`) VALUES
(1, 1, 1),
(2, 1, 1),
(3, 1, 1),
(9, 2, 2),
(10, 1, 2),
(11, 3, 4),
(12, 1, 4),
(13, 3, 5),
(14, 3, 2),
(15, 4, 2),
(16, 4, 4),
(17, 4, 5),
(18, 1, 6),
(19, 5, 2),
(20, 5, 4),
(21, 5, 1),
(22, 5, 6),
(23, 1, 7),
(24, 1, 12),
(25, 6, 1),
(26, 6, 2),
(27, 6, 6),
(28, 6, 4),
(29, 1, 13);

-- --------------------------------------------------------

--
-- Struttura della tabella `campi`
--

DROP TABLE IF EXISTS `campi`;
CREATE TABLE `campi` (
  `id_campo` int(11) NOT NULL,
  `indirizzo` varchar(200) NOT NULL,
  `terreno` varchar(50) NOT NULL,
  `spogliatoi` tinyint(1) NOT NULL,
  `n_giocatori` int(11) NOT NULL,
  `costo` int(11) NOT NULL,
  `docce` tinyint(1) NOT NULL,
  `id_torneo` int(11) NOT NULL,
  `id_utente` int(11) NOT NULL,
  `latitudine` double NOT NULL,
  `longitudine` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `campi`
--

INSERT INTO `campi` (`id_campo`, `indirizzo`, `terreno`, `spogliatoi`, `n_giocatori`, `costo`, `docce`, `id_torneo`, `id_utente`, `latitudine`, `longitudine`) VALUES
(4, 'Via Enrico Fermi 8', 'Sintetico', 0, 9, 0, 1, 0, 1, 0, 0),
(5, 'Viale sarta 10', 'Sintetico', 0, 5, 60, 1, 0, 1, 0, 0),
(6, 'Viale sarta 10', 'Sintetico', 0, 5, 60, 1, 0, 1, 0, 0),
(7, 'Via skibidi', 'Ghiaia', 0, 11, 22, 0, 0, 4, 0, 0),
(8, 'Via Roma', 'Erba', 1, 5, 200, 1, 0, 1, 44.697356342713356, 7.934153456076607),
(9, 'Via Roma Stezzano', 'Sintetico', 0, 5, 60, 1, 0, 1, 45.649278312912145, 9.65386586933779),
(10, 'Via Collodi Almenno San Salvatore', 'Sintetico', 1, 5, 30, 1, 0, 1, 45.7491828, 9.598398),
(11, 'Via Colombo', 'Erba', 1, 5, 50, 1, 0, 1, 41.862404, 12.4980628),
(12, 'Viale dei caduti, Almenno San Salvatore ', 'Erba', 1, 5, 60, 1, 0, 1, 45.7487411, 9.5931427),
(14, 'Via gerosa, Almenno San Salvatore', 'Sintetico', 1, 8, 60, 1, 0, 1, 45.7496299, 9.584818);

-- --------------------------------------------------------

--
-- Struttura della tabella `campo_torneo`
--

DROP TABLE IF EXISTS `campo_torneo`;
CREATE TABLE `campo_torneo` (
  `id_campo_torneo` int(11) NOT NULL,
  `id_campo` int(11) NOT NULL,
  `id_torneo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `campo_torneo`
--

INSERT INTO `campo_torneo` (`id_campo_torneo`, `id_campo`, `id_torneo`) VALUES
(1, 4, 2),
(2, 5, 2),
(3, 7, 2),
(4, 4, 3),
(5, 5, 3),
(6, 7, 3),
(7, 4, 4),
(8, 6, 4),
(9, 7, 4),
(10, 4, 5),
(11, 6, 5),
(12, 9, 5),
(13, 10, 5),
(14, 11, 5),
(15, 4, 6),
(16, 5, 6),
(17, 7, 6),
(18, 8, 6),
(19, 9, 6),
(20, 10, 6),
(29, 4, 13),
(30, 5, 13),
(31, 4, 14),
(32, 5, 14),
(33, 8, 14),
(38, 5, 17),
(39, 8, 17);

-- --------------------------------------------------------

--
-- Struttura della tabella `commenti`
--

DROP TABLE IF EXISTS `commenti`;
CREATE TABLE `commenti` (
  `id_commento` int(11) NOT NULL,
  `testo` varchar(255) NOT NULL,
  `data_commento` datetime NOT NULL,
  `id_utente` int(11) NOT NULL,
  `id_campo` int(11) DEFAULT NULL,
  `id_calcetto` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `commenti`
--

INSERT INTO `commenti` (`id_commento`, `testo`, `data_commento`, `id_utente`, `id_campo`, `id_calcetto`) VALUES
(3, 'Bellissimo', '2025-05-05 08:53:19', 1, 4, NULL),
(4, 'Bellissimo', '2025-05-05 08:54:59', 1, 4, NULL),
(5, 'Ha spaccato', '2025-05-18 12:03:24', 1, NULL, 9);

-- --------------------------------------------------------

--
-- Struttura della tabella `messaggi`
--

DROP TABLE IF EXISTS `messaggi`;
CREATE TABLE `messaggi` (
  `id_messaggio` int(11) NOT NULL,
  `id_calcetto` int(11) NOT NULL,
  `id_utente` int(11) NOT NULL,
  `testo` text NOT NULL,
  `data_ora` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `messaggi`
--

INSERT INTO `messaggi` (`id_messaggio`, `id_calcetto`, `id_utente`, `testo`, `data_ora`) VALUES
(1, 7, 1, 'Ciao', '2025-05-05 09:34:55'),
(2, 4, 5, 'Ciaoooo', '2025-05-05 09:35:39'),
(3, 4, 1, 'Ciao bello', '2025-05-05 09:36:08'),
(4, 4, 6, 'CIaoooo', '2025-05-11 16:12:25');

-- --------------------------------------------------------

--
-- Struttura della tabella `partite`
--

DROP TABLE IF EXISTS `partite`;
CREATE TABLE `partite` (
  `id_partita` int(11) NOT NULL,
  `id_squadra1` int(11) NOT NULL,
  `id_squadra2` int(11) NOT NULL,
  `gol_1` int(11) DEFAULT NULL,
  `gol_2` int(11) DEFAULT NULL,
  `data_partita` date DEFAULT NULL,
  `orario` time DEFAULT NULL,
  `id_torneo` int(11) NOT NULL,
  `fase_finale` tinyint(1) DEFAULT 0,
  `turno` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `partite`
--

INSERT INTO `partite` (`id_partita`, `id_squadra1`, `id_squadra2`, `gol_1`, `gol_2`, `data_partita`, `orario`, `id_torneo`, `fase_finale`, `turno`) VALUES
(4, 1, 2, 1, 0, '2025-10-05', '10:00:00', 2, 0, 1),
(5, 3, 4, 5, 0, '2026-10-05', '10:00:00', 3, 0, 1),
(6, 3, 5, 3, 4, NULL, NULL, 3, 0, 1),
(7, 3, 6, 3, 1, NULL, NULL, 3, 0, 1),
(8, 3, 7, 1, 4, NULL, NULL, 3, 0, 1),
(9, 4, 5, 0, 0, NULL, NULL, 3, 0, 1),
(10, 4, 6, 3, 4, NULL, NULL, 3, 0, 1),
(11, 4, 7, 1, 1, NULL, NULL, 3, 0, 1),
(12, 5, 6, 2, 0, NULL, NULL, 3, 0, 1),
(13, 5, 7, 3, 1, '2025-10-05', '11:11:00', 3, 0, 1),
(14, 6, 7, 0, 0, NULL, NULL, 3, 0, 1),
(15, 8, 9, 1, 0, NULL, NULL, 4, 0, 1),
(16, 8, 10, 0, 1, NULL, NULL, 4, 0, 1),
(17, 9, 10, 1, 1, NULL, NULL, 4, 0, 1),
(18, 11, 12, 1, 2, NULL, NULL, 5, 0, 1),
(19, 11, 13, 0, 0, NULL, NULL, 5, 0, 1),
(20, 12, 13, 0, 1, NULL, NULL, 5, 0, 1),
(21, 14, 15, 0, 0, NULL, NULL, 6, 0, 1),
(22, 14, 16, 0, 0, NULL, NULL, 6, 0, 1),
(23, 14, 17, 0, 0, NULL, NULL, 6, 0, 1),
(24, 15, 16, 0, 0, NULL, NULL, 6, 0, 1),
(25, 15, 17, 0, 0, NULL, NULL, 6, 0, 1),
(26, 16, 17, 0, 0, NULL, NULL, 6, 0, 1),
(44, 16, 17, NULL, NULL, NULL, NULL, 6, 1, 1),
(45, 13, 12, NULL, NULL, NULL, NULL, 5, 1, 1),
(50, 2, 1, NULL, NULL, NULL, NULL, 2, 1, 1),
(53, 8, 10, NULL, NULL, NULL, NULL, 4, 1, 1),
(54, 2, 1, NULL, NULL, NULL, NULL, 2, 1, 1),
(55, 5, 3, NULL, NULL, NULL, NULL, 3, 1, 1),
(80, 41, 42, 1, 0, '0000-00-00', '00:00:00', 13, 0, 1),
(81, 41, 43, 0, 1, '0000-00-00', '00:00:00', 13, 0, 1),
(82, 42, 43, 0, 0, '0000-00-00', '00:00:00', 13, 0, 1),
(83, 43, 41, 1, 2, '0000-00-00', '00:00:00', 13, 1, 1),
(95, 44, 45, NULL, NULL, NULL, NULL, 14, 0, 1),
(96, 44, 46, NULL, NULL, NULL, NULL, 14, 0, 1),
(97, 44, 47, NULL, NULL, NULL, NULL, 14, 0, 1),
(98, 45, 46, NULL, NULL, NULL, NULL, 14, 0, 1),
(99, 45, 47, NULL, NULL, NULL, NULL, 14, 0, 1),
(100, 46, 47, NULL, NULL, NULL, NULL, 14, 0, 1),
(112, 58, 56, 0, 1, '0000-00-00', '00:00:00', 17, 1, 1),
(113, 59, 57, 1, 0, '0000-00-00', '00:00:00', 17, 1, 1),
(114, 56, 59, 2, 0, '0000-00-00', '00:00:00', 17, 1, 2);

-- --------------------------------------------------------

--
-- Struttura della tabella `recensioni`
--

DROP TABLE IF EXISTS `recensioni`;
CREATE TABLE `recensioni` (
  `Id_recensione` int(11) NOT NULL,
  `valutazione` int(11) NOT NULL,
  `commento` varchar(500) NOT NULL,
  `data_recensione` date NOT NULL,
  `id_utente` int(11) NOT NULL,
  `id_campo` int(11) DEFAULT NULL,
  `id_calcetto` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `recensioni`
--

INSERT INTO `recensioni` (`Id_recensione`, `valutazione`, `commento`, `data_recensione`, `id_utente`, `id_campo`, `id_calcetto`) VALUES
(1, 3, 'Campo molto bello', '2025-05-04', 1, 8, NULL),
(2, 3, 'Calcetto interessante e divertente', '2025-05-04', 1, NULL, 6),
(3, 2, 'Un po trasandato', '2025-05-18', 1, 10, NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `richieste_squadra`
--

DROP TABLE IF EXISTS `richieste_squadra`;
CREATE TABLE `richieste_squadra` (
  `id_richiesta` int(11) NOT NULL,
  `id_utente` int(11) NOT NULL,
  `id_squadra` int(11) NOT NULL,
  `stato` enum('in_attesa','accettata','rifiutata') DEFAULT 'in_attesa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `richieste_squadra`
--

INSERT INTO `richieste_squadra` (`id_richiesta`, `id_utente`, `id_squadra`, `stato`) VALUES
(1, 1, 1, 'rifiutata'),
(2, 5, 1, 'accettata'),
(3, 6, 1, 'accettata'),
(4, 4, 8, 'accettata');

-- --------------------------------------------------------

--
-- Struttura della tabella `squadre`
--

DROP TABLE IF EXISTS `squadre`;
CREATE TABLE `squadre` (
  `Id_squadra` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `id_torneo` int(11) NOT NULL,
  `id_creatore` int(11) DEFAULT NULL,
  `girone` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `squadre`
--

INSERT INTO `squadre` (`Id_squadra`, `nome`, `id_torneo`, `id_creatore`, `girone`) VALUES
(1, 'Real Madrid', 2, 1, NULL),
(2, 'Barcellona', 2, 6, NULL),
(3, 'Real Madrid', 3, 1, NULL),
(4, 'Barcellona', 3, 1, NULL),
(5, 'Juve', 3, 6, NULL),
(6, 'Inter', 3, 2, NULL),
(7, 'Milan', 3, 5, NULL),
(8, 'Atalanta', 4, 1, NULL),
(9, 'Bologna', 4, 2, NULL),
(10, 'Udinese', 4, 5, NULL),
(11, 'Real Madrid', 5, 6, NULL),
(12, 'Juve', 5, 1, NULL),
(13, 'Inter', 5, 3, NULL),
(14, 'Juve', 6, 1, NULL),
(15, 'Milan', 6, 3, NULL),
(16, 'Roma', 6, 2, NULL),
(17, 'Napoli', 6, 7, NULL),
(41, 'Real Madrid', 13, 1, 'A'),
(42, 'Juve', 13, 6, 'A'),
(43, 'aa', 13, 7, 'A'),
(44, 'Barcellona', 14, 1, NULL),
(45, 'Real Madrid', 14, 3, NULL),
(46, 'Napoli', 14, 6, NULL),
(47, 'Juve', 14, 2, NULL),
(56, 'Real Madrid', 17, 1, NULL),
(57, 'Juve', 17, 3, NULL),
(58, 'Barcellona', 17, 6, NULL),
(59, 'Bologna', 17, 2, NULL),
(60, 'Cagliari', 2, 3, NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `tornei`
--

DROP TABLE IF EXISTS `tornei`;
CREATE TABLE `tornei` (
  `Id_torneo` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `data_inizio` date NOT NULL,
  `data_fine` date NOT NULL,
  `ora_inizio` time NOT NULL,
  `ora_fine` time NOT NULL,
  `certificato_medico` tinyint(1) NOT NULL,
  `docce` tinyint(1) NOT NULL,
  `note` varchar(500) NOT NULL,
  `tipologia` varchar(200) NOT NULL,
  `id_utente` int(11) NOT NULL,
  `max_giocatori` int(11) NOT NULL DEFAULT 11,
  `max_squadre` int(11) NOT NULL DEFAULT 16
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `tornei`
--

INSERT INTO `tornei` (`Id_torneo`, `nome`, `data_inizio`, `data_fine`, `ora_inizio`, `ora_fine`, `certificato_medico`, `docce`, `note`, `tipologia`, `id_utente`, `max_giocatori`, `max_squadre`) VALUES
(1, '0', '2025-10-05', '2025-10-30', '10:00:00', '12:00:00', 1, 0, 'AAA', 'Singolo', 1, 11, 16),
(2, 'Torneo tungtung', '2025-10-05', '2025-10-11', '10:00:00', '12:00:00', 1, 1, 'AAA', 'Singolo', 1, 11, 16),
(3, 'Torneo la gabbia', '2025-05-06', '2025-06-06', '10:00:00', '22:00:00', 1, 1, 'Torneo per i giocatori di calcio....', 'Girone Unico', 1, 11, 8),
(4, 'Torneo Abc', '2025-12-01', '2025-12-11', '10:00:00', '20:00:00', 1, 0, 'Montepremi in denaro', 'Girone Unico', 1, 8, 16),
(5, 'ciao', '2025-04-09', '2025-02-05', '10:00:00', '12:00:00', 1, 1, '', 'Girone Unico', 6, 10, 10),
(6, 'Torneo ciaoo', '2026-03-04', '2026-03-05', '10:00:00', '23:00:00', 1, 1, '', 'Girone Unico', 1, 11, 8),
(13, 'aei', '2025-05-31', '2025-07-05', '10:00:00', '22:00:00', 1, 1, '', 'Gironi Multipli', 1, 11, 16),
(14, 'Elim diretta', '2025-04-02', '2025-06-10', '10:00:00', '20:00:00', 1, 1, '', 'Eliminazione Diretta', 1, 11, 4),
(17, 'ed', '2025-10-04', '2025-10-05', '10:00:00', '22:20:00', 1, 1, '', 'Eliminazione Diretta', 1, 11, 4);

-- --------------------------------------------------------

--
-- Struttura della tabella `utente_squadra`
--

DROP TABLE IF EXISTS `utente_squadra`;
CREATE TABLE `utente_squadra` (
  `id_utente_squadra` int(11) NOT NULL,
  `id_utente` int(11) NOT NULL,
  `id_squadra` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utente_squadra`
--

INSERT INTO `utente_squadra` (`id_utente_squadra`, `id_utente`, `id_squadra`) VALUES
(1, 1, 1),
(2, 5, 1),
(4, 6, 1),
(5, 6, 2),
(6, 1, 3),
(7, 1, 4),
(8, 6, 5),
(9, 2, 6),
(10, 5, 7),
(11, 1, 8),
(12, 2, 9),
(13, 4, 8),
(14, 5, 10),
(15, 6, 11),
(16, 1, 12),
(17, 3, 13),
(18, 1, 14),
(19, 3, 15),
(20, 2, 16),
(21, 7, 17),
(45, 1, 41),
(46, 6, 42),
(47, 7, 43),
(48, 1, 44),
(49, 3, 45),
(50, 6, 46),
(51, 2, 47),
(60, 1, 56),
(61, 3, 57),
(62, 6, 58),
(63, 2, 59),
(64, 3, 60);

-- --------------------------------------------------------

--
-- Struttura della tabella `utenti`
--

DROP TABLE IF EXISTS `utenti`;
CREATE TABLE `utenti` (
  `id_utente` int(11) NOT NULL,
  `nickname` varchar(100) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `cognome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `psw` varchar(255) NOT NULL,
  `data_nascita` datetime NOT NULL,
  `telefono` varchar(10) NOT NULL,
  `indirizzo` varchar(155) NOT NULL,
  `paese` varchar(150) NOT NULL,
  `sesso` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utenti`
--

INSERT INTO `utenti` (`id_utente`, `nickname`, `nome`, `cognome`, `email`, `psw`, `data_nascita`, `telefono`, `indirizzo`, `paese`, `sesso`) VALUES
(1, 'alerota', 'Alessandro', 'Rota', 'rotaale05102006@gmail.com', '$2y$10$8UJiXD7Ic/.zqto2hcnZSe.uRfUn7F4R3rZiyLN2P0u2gymM.ft8K', '2006-10-05 00:00:00', '3475008199', 'Via Collodi,10', 'Almenno San Salvatore', 1),
(2, 'filocale', 'Filippo', 'Calegari', 'filocale@gmail.com', '$2y$10$h14sKXFYa1tSSzXzjfSryexyjzFGbD8E2axjKU1PJtY9SVao9nMzi', '2025-02-20 00:00:00', '3456789876', 'Via Molina 3', 'Stezzano', 1),
(3, 'andrebona', 'Andrea', 'Bonaiti', 'andrebona@gmail.com', '$2y$10$/npfX7QNbWdDAWCEcnYeX.CRTA3P4rPBvF2653KLaaB9W2ZlNz9qm', '2006-07-11 00:00:00', '3456789087', 'Via Ca Campo 10', 'Strosa', 1),
(4, 'mirkocolo', 'Mirko', 'Colo', 'mirko@gmail.com', '$2y$10$Ef0lQKYHoXsOmU1V5viZCuR4yr/66noVDVaelQROWXteZ8UKBUWyK', '2006-11-05 00:00:00', '5927232222', 'Via delle bollus', 'Alzano Lombardo', 0),
(5, 'francyrota', 'Francesco', 'Rota', 'chiccorota@gmail.com', '$2y$10$eRcQjIQxWTTPrHgvqDF2MOTS2FCSKs4Z8iycbAMZgIx0ZZb8XMLDy', '2011-11-22 00:00:00', '3475008170', 'Via Carlo Collodi 10', 'Italia', 1),
(6, 'marota', 'Marco', 'Rota', 'rotamarco71@alice.it', '$2y$10$9W98VcIR3J8oa2wxZ5F2JOOL2ArgsLIKc36sq0Ysv6GslMSHChrP6', '1971-03-31 00:00:00', '3475008000', 'Via Carlo Collodi 6', 'Italia', 1),
(7, 'manu', 'Manu', 'Frosio', 'manufrosio73@gmail.com', '$2y$10$0De/lnh2UomNNtwrg489Vuf4NkQrdGrQslS9UxLyx7jvUwvWrdV1W', '1973-11-26 00:00:00', '3564563232', 'Via Collodi 10', 'Almenno San Salvatore', 0),
(8, 'Mariossi', 'Mario', 'Rossi', 'mariossi@gmail.com', '$2y$10$0BsGoAXmInvWjANVGkowIe2ITMCySkIylVvkURbAJCcC2BW2tNV36', '2005-10-05 00:00:00', '3467003124', 'Via Roccanelle 1', 'Medolago', 1);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `amicizie`
--
ALTER TABLE `amicizie`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_mittente` (`id_mittente`),
  ADD KEY `id_destinatario` (`id_destinatario`);

--
-- Indici per le tabelle `calcetti`
--
ALTER TABLE `calcetti`
  ADD PRIMARY KEY (`id_calcetto`),
  ADD KEY `id_campo` (`id_campo`),
  ADD KEY `id_utente` (`id_utente`);

--
-- Indici per le tabelle `calcetto_utente`
--
ALTER TABLE `calcetto_utente`
  ADD PRIMARY KEY (`Id_calcettoutente`),
  ADD KEY `Id_utente` (`Id_utente`),
  ADD KEY `Id_calcetto` (`Id_calcetto`);

--
-- Indici per le tabelle `campi`
--
ALTER TABLE `campi`
  ADD PRIMARY KEY (`id_campo`),
  ADD KEY `id_torneo` (`id_torneo`),
  ADD KEY `id_utente` (`id_utente`);

--
-- Indici per le tabelle `campo_torneo`
--
ALTER TABLE `campo_torneo`
  ADD PRIMARY KEY (`id_campo_torneo`),
  ADD KEY `id_campo` (`id_campo`),
  ADD KEY `id_torneo` (`id_torneo`);

--
-- Indici per le tabelle `commenti`
--
ALTER TABLE `commenti`
  ADD PRIMARY KEY (`id_commento`),
  ADD KEY `id_utente` (`id_utente`),
  ADD KEY `id_campo` (`id_campo`),
  ADD KEY `id_calcetto` (`id_calcetto`);

--
-- Indici per le tabelle `messaggi`
--
ALTER TABLE `messaggi`
  ADD PRIMARY KEY (`id_messaggio`),
  ADD KEY `id_calcetto` (`id_calcetto`),
  ADD KEY `id_utente` (`id_utente`);

--
-- Indici per le tabelle `partite`
--
ALTER TABLE `partite`
  ADD PRIMARY KEY (`id_partita`),
  ADD KEY `id_squadra1` (`id_squadra1`),
  ADD KEY `id_squadra2` (`id_squadra2`),
  ADD KEY `id_torneo` (`id_torneo`);

--
-- Indici per le tabelle `recensioni`
--
ALTER TABLE `recensioni`
  ADD PRIMARY KEY (`Id_recensione`),
  ADD KEY `id_utente` (`id_utente`),
  ADD KEY `id_campo` (`id_campo`),
  ADD KEY `id_calcetto` (`id_calcetto`);

--
-- Indici per le tabelle `richieste_squadra`
--
ALTER TABLE `richieste_squadra`
  ADD PRIMARY KEY (`id_richiesta`),
  ADD KEY `id_utente` (`id_utente`),
  ADD KEY `id_squadra` (`id_squadra`);

--
-- Indici per le tabelle `squadre`
--
ALTER TABLE `squadre`
  ADD PRIMARY KEY (`Id_squadra`),
  ADD KEY `id_torneo` (`id_torneo`);

--
-- Indici per le tabelle `tornei`
--
ALTER TABLE `tornei`
  ADD PRIMARY KEY (`Id_torneo`),
  ADD KEY `id_utente` (`id_utente`);

--
-- Indici per le tabelle `utente_squadra`
--
ALTER TABLE `utente_squadra`
  ADD PRIMARY KEY (`id_utente_squadra`),
  ADD KEY `id_utente` (`id_utente`),
  ADD KEY `id_squadra` (`id_squadra`);

--
-- Indici per le tabelle `utenti`
--
ALTER TABLE `utenti`
  ADD PRIMARY KEY (`id_utente`),
  ADD UNIQUE KEY `nickname` (`nickname`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `amicizie`
--
ALTER TABLE `amicizie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `calcetti`
--
ALTER TABLE `calcetti`
  MODIFY `id_calcetto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT per la tabella `calcetto_utente`
--
ALTER TABLE `calcetto_utente`
  MODIFY `Id_calcettoutente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT per la tabella `campi`
--
ALTER TABLE `campi`
  MODIFY `id_campo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT per la tabella `campo_torneo`
--
ALTER TABLE `campo_torneo`
  MODIFY `id_campo_torneo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT per la tabella `commenti`
--
ALTER TABLE `commenti`
  MODIFY `id_commento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `messaggi`
--
ALTER TABLE `messaggi`
  MODIFY `id_messaggio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `partite`
--
ALTER TABLE `partite`
  MODIFY `id_partita` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT per la tabella `recensioni`
--
ALTER TABLE `recensioni`
  MODIFY `Id_recensione` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `richieste_squadra`
--
ALTER TABLE `richieste_squadra`
  MODIFY `id_richiesta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `squadre`
--
ALTER TABLE `squadre`
  MODIFY `Id_squadra` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT per la tabella `tornei`
--
ALTER TABLE `tornei`
  MODIFY `Id_torneo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT per la tabella `utente_squadra`
--
ALTER TABLE `utente_squadra`
  MODIFY `id_utente_squadra` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT per la tabella `utenti`
--
ALTER TABLE `utenti`
  MODIFY `id_utente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `amicizie`
--
ALTER TABLE `amicizie`
  ADD CONSTRAINT `amicizie_ibfk_1` FOREIGN KEY (`id_mittente`) REFERENCES `utenti` (`id_utente`),
  ADD CONSTRAINT `amicizie_ibfk_2` FOREIGN KEY (`id_destinatario`) REFERENCES `utenti` (`id_utente`);

--
-- Limiti per la tabella `calcetti`
--
ALTER TABLE `calcetti`
  ADD CONSTRAINT `calcetti_ibfk_1` FOREIGN KEY (`id_campo`) REFERENCES `campi` (`id_campo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `calcetti_ibfk_2` FOREIGN KEY (`id_utente`) REFERENCES `utenti` (`id_utente`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `calcetto_utente`
--
ALTER TABLE `calcetto_utente`
  ADD CONSTRAINT `calcetto_utente_ibfk_1` FOREIGN KEY (`Id_utente`) REFERENCES `utenti` (`id_utente`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `calcetto_utente_ibfk_2` FOREIGN KEY (`Id_calcetto`) REFERENCES `calcetti` (`id_calcetto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `campi`
--
ALTER TABLE `campi`
  ADD CONSTRAINT `campi_ibfk_2` FOREIGN KEY (`id_utente`) REFERENCES `utenti` (`id_utente`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `campo_torneo`
--
ALTER TABLE `campo_torneo`
  ADD CONSTRAINT `campo_torneo_ibfk_1` FOREIGN KEY (`id_torneo`) REFERENCES `tornei` (`Id_torneo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `campo_torneo_ibfk_2` FOREIGN KEY (`id_campo`) REFERENCES `campi` (`id_campo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `commenti`
--
ALTER TABLE `commenti`
  ADD CONSTRAINT `commenti_ibfk_1` FOREIGN KEY (`id_calcetto`) REFERENCES `calcetti` (`id_calcetto`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `commenti_ibfk_2` FOREIGN KEY (`id_utente`) REFERENCES `utenti` (`id_utente`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `commenti_ibfk_3` FOREIGN KEY (`id_campo`) REFERENCES `campi` (`id_campo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `messaggi`
--
ALTER TABLE `messaggi`
  ADD CONSTRAINT `messaggi_ibfk_1` FOREIGN KEY (`id_calcetto`) REFERENCES `calcetti` (`id_calcetto`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `messaggi_ibfk_2` FOREIGN KEY (`id_utente`) REFERENCES `utenti` (`id_utente`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `partite`
--
ALTER TABLE `partite`
  ADD CONSTRAINT `partite_ibfk_1` FOREIGN KEY (`id_torneo`) REFERENCES `tornei` (`Id_torneo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `partite_ibfk_2` FOREIGN KEY (`id_squadra1`) REFERENCES `squadre` (`Id_squadra`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `partite_ibfk_3` FOREIGN KEY (`id_squadra2`) REFERENCES `squadre` (`Id_squadra`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `recensioni`
--
ALTER TABLE `recensioni`
  ADD CONSTRAINT `recensioni_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `utenti` (`id_utente`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `recensioni_ibfk_2` FOREIGN KEY (`id_campo`) REFERENCES `campi` (`id_campo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `recensioni_ibfk_3` FOREIGN KEY (`id_calcetto`) REFERENCES `calcetti` (`id_calcetto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `richieste_squadra`
--
ALTER TABLE `richieste_squadra`
  ADD CONSTRAINT `richieste_squadra_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `utenti` (`id_utente`) ON DELETE CASCADE,
  ADD CONSTRAINT `richieste_squadra_ibfk_2` FOREIGN KEY (`id_squadra`) REFERENCES `squadre` (`Id_squadra`) ON DELETE CASCADE;

--
-- Limiti per la tabella `squadre`
--
ALTER TABLE `squadre`
  ADD CONSTRAINT `squadre_ibfk_1` FOREIGN KEY (`id_torneo`) REFERENCES `tornei` (`Id_torneo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `utente_squadra`
--
ALTER TABLE `utente_squadra`
  ADD CONSTRAINT `utente_squadra_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `utenti` (`id_utente`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `utente_squadra_ibfk_2` FOREIGN KEY (`id_squadra`) REFERENCES `squadre` (`Id_squadra`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
