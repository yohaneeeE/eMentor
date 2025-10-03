-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Oct 03, 2025 at 03:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `careerguidance`
--

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `career_id` int(11) NOT NULL,
  `certificate_title` varchar(255) NOT NULL,
  `provider` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `skills` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `career_id`, `certificate_title`, `provider`, `description`, `skills`) VALUES
(1, 1, 'Oracle Certified Java Programmer', 'Oracle', 'Certification in Java programming for enterprise applications.', 'Java, OOP, Debugging'),
(2, 1, 'Microsoft Certified: Azure Developer Associate', 'Microsoft', 'Validates cloud application development skills using Azure.', 'Cloud Development, APIs, C#'),
(3, 1, 'Certified Software Development Professional (CSDP)', 'IEEE', 'Professional-level certification in software engineering practices.', 'SDLC, Design Patterns, Testing'),
(4, 2, 'FreeCodeCamp Responsive Web Design', 'FreeCodeCamp', 'Covers HTML, CSS, and responsive design principles.', 'HTML, CSS, Flexbox, Grid'),
(5, 2, 'Meta Front-End Developer Certificate', 'Coursera/Meta', 'Teaches building websites with React and modern front-end tools.', 'React, JavaScript, UI'),
(6, 2, 'W3C Front-End Web Developer Certificate', 'edX/W3C', 'Covers HTML5, CSS, and JavaScript fundamentals.', 'Web Standards, Accessibility, JS'),
(7, 3, 'Unity Certified Associate: Game Developer', 'Unity', 'Official certification for building games with Unity.', 'Unity, C#, Game Mechanics'),
(8, 3, 'Unreal Engine Developer Certification', 'Epic Games', 'Covers Unreal Engine game design and development.', 'Unreal, C++, Blueprints'),
(9, 3, 'Certified Game Designer', 'IGDA', 'Validates skills in storytelling, mechanics, and design.', 'Level Design, Storytelling, UX'),
(10, 4, 'Google Associate Android Developer', 'Google', 'Certification for building Android apps in Java/Kotlin.', 'Android SDK, Kotlin, UI'),
(11, 4, 'Apple Certified iOS App Developer', 'Apple', 'Professional certificate for building iOS apps.', 'Swift, iOS Frameworks, Xcode'),
(12, 4, 'Flutter Developer Certificate', 'Google/Udemy', 'Covers cross-platform app development using Flutter.', 'Dart, Widgets, Cross-Platform'),
(13, 5, 'MongoDB Certified Developer Associate', 'MongoDB', 'Certification in database development with MongoDB.', 'NoSQL, Databases, CRUD'),
(14, 5, 'The Odin Project Full Stack Certificate', 'The Odin Project', 'Covers front-end and back-end web development.', 'HTML, CSS, Node.js, SQL'),
(15, 5, 'Meta Full Stack Developer Certificate', 'Coursera/Meta', 'Full stack certificate program for web developers.', 'React, Node.js, API, SQL'),
(16, 6, 'Frontend Developer Nanodegree', 'Udacity', 'Covers advanced frontend development with JavaScript.', 'HTML, CSS, JavaScript, DOM'),
(17, 6, 'Google UX Design Certificate', 'Google', 'Focuses on UX principles and UI design.', 'Wireframes, Prototyping, UX Research'),
(18, 6, 'React Developer Certificate', 'Codecademy', 'Certification in React front-end development.', 'React, JSX, Hooks'),
(19, 7, 'Node.js Developer Certificate', 'OpenJS Foundation', 'Certification in Node.js server-side development.', 'Node.js, APIs, Express'),
(20, 7, 'PHP Zend Certified Engineer', 'Zend', 'Validates PHP and backend development skills.', 'PHP, SQL, Security'),
(21, 7, 'Python Backend Developer Certificate', 'Udemy', 'Backend development certification using Python.', 'Flask, Django, APIs'),
(22, 8, 'Adobe Certified Professional: UX Design', 'Adobe', 'Professional UI/UX certificate.', 'Adobe XD, Figma, UX'),
(23, 8, 'Google UX Design Professional Certificate', 'Google/Coursera', 'Foundational course in UX/UI.', 'User Research, Prototyping'),
(24, 8, 'Interaction Design Certificate', 'Interaction Design Foundation', 'Covers human-centered design.', 'UX, Usability, Prototyping'),
(25, 9, 'IBM Data Science Professional Certificate', 'Coursera/IBM', 'Covers data science tools and machine learning.', 'Python, Pandas, ML'),
(26, 9, 'Microsoft Certified: Data Scientist Associate', 'Microsoft', 'Certification for applied data science.', 'Azure ML, Python, Data Analysis'),
(27, 9, 'Google Cloud Professional Data Scientist', 'Google Cloud', 'Covers ML and AI deployment on GCP.', 'TensorFlow, MLops'),
(28, 10, 'Google Data Analytics Certificate', 'Google', 'Data analytics for business decision making.', 'SQL, Tableau, Excel'),
(29, 10, 'Microsoft Power BI Data Analyst Associate', 'Microsoft', 'Focus on dashboards and reporting.', 'Power BI, Data Visualization'),
(30, 10, 'Certified Business Data Analyst (CBDA)', 'IIBA', 'Global certification for data analysis.', 'Requirements, Analytics'),
(31, 11, 'TensorFlow Developer Certificate', 'Google', 'Certification in deep learning models.', 'TensorFlow, Neural Networks'),
(32, 11, 'AWS Certified Machine Learning – Specialty', 'Amazon AWS', 'Covers ML workflows on AWS.', 'SageMaker, MLops'),
(33, 11, 'Artificial Intelligence Engineer Certificate', 'Simplilearn', 'Professional AI engineer course.', 'Deep Learning, AI Models'),
(34, 12, 'Machine Learning Specialization', 'Coursera/Stanford', 'Taught by Andrew Ng on ML principles.', 'ML Algorithms, Supervised Learning'),
(35, 12, 'Certified Machine Learning Engineer', 'DataCamp', 'Certification in ML development.', 'Python, Scikit-learn'),
(36, 12, 'ML Ops Certification', 'Udacity', 'Productionizing ML pipelines.', 'CI/CD, ML Ops'),
(37, 13, 'Certified Business Intelligence Professional (CBIP)', 'TDWI', 'Certification in BI reporting.', 'BI, Analytics, Dashboards'),
(38, 13, 'Tableau Desktop Specialist', 'Tableau', 'Certification in data visualization.', 'Dashboards, BI Tools'),
(39, 13, 'Qlik Sense Business Analyst', 'Qlik', 'Certificate for BI dashboards.', 'Data Viz, BI'),
(40, 14, 'CompTIA Security+', 'CompTIA', 'Foundational cybersecurity certification.', 'Security, Networks, Threats'),
(41, 14, 'Certified Ethical Hacker (CEH)', 'EC-Council', 'Certification in penetration testing.', 'Hacking, Testing, Security'),
(42, 14, 'Cisco CyberOps Associate', 'Cisco', 'Cybersecurity operations certification.', 'SOC, Monitoring, Security'),
(43, 15, 'Cisco CCNA', 'Cisco', 'Networking certification.', 'TCP/IP, Routing, Switching'),
(44, 15, 'CompTIA Network+', 'CompTIA', 'General networking certificate.', 'Networking, Troubleshooting'),
(45, 15, 'Juniper JNCIA', 'Juniper', 'Network fundamentals certification.', 'Networking, Routing'),
(46, 16, 'CISM (Certified Information Security Manager)', 'ISACA', 'Certification in managing info security.', 'Security Policies, Governance'),
(47, 16, 'CISA (Certified Information Systems Auditor)', 'ISACA', 'Certification in auditing IS systems.', 'Auditing, Risk Management'),
(48, 16, 'ISO 27001 Lead Implementer', 'PECB', 'Certification in ISO security standards.', 'ISO, Security'),
(49, 17, 'Offensive Security Certified Professional (OSCP)', 'OffSec', 'Hands-on penetration testing.', 'Ethical Hacking, Linux'),
(50, 17, 'Certified Penetration Tester (CPT)', 'IACRB', 'Professional penetration testing cert.', 'PenTest, Kali Linux'),
(51, 17, 'GIAC Penetration Tester (GPEN)', 'GIAC', 'Certification in penetration testing.', 'Security, PenTest'),
(52, 18, 'AWS Certified Solutions Architect', 'Amazon AWS', 'Certification in cloud architecture.', 'AWS, Cloud'),
(53, 18, 'Microsoft Certified: Azure Solutions Architect', 'Microsoft', 'Azure cloud certification.', 'Azure, Cloud Infrastructure'),
(54, 18, 'Google Cloud Architect', 'Google', 'Certification for GCP cloud architecture.', 'GCP, Cloud'),
(55, 19, 'Linux Professional Institute Certification (LPIC-1)', 'LPI', 'Linux system administration certification.', 'Linux, Bash'),
(56, 19, 'Microsoft Certified: Windows Server Administrator', 'Microsoft', 'Windows server certification.', 'Windows Server, Admin'),
(57, 19, 'Red Hat Certified System Administrator (RHCSA)', 'Red Hat', 'Linux sysadmin certification.', 'Linux, Admin'),
(58, 20, 'Certified Kubernetes Administrator (CKA)', 'CNCF', 'Certification in Kubernetes admin.', 'Kubernetes, Containers'),
(59, 20, 'Docker Certified Associate', 'Docker', 'Certification for container tech.', 'Docker, Containers'),
(60, 20, 'Jenkins Engineer Certification', 'CloudBees', 'CI/CD pipeline certification.', 'Jenkins, CI/CD'),
(61, 21, 'CompTIA IT Fundamentals+', 'CompTIA', 'Entry-level IT support certificate.', 'IT Basics, Troubleshooting'),
(62, 21, 'HDI Support Center Analyst', 'HDI', 'Certification for IT support helpdesk.', 'Helpdesk, Communication'),
(63, 21, 'Microsoft 365 Certified: Modern Desktop Administrator', 'Microsoft', 'Certification for IT support admins.', 'Windows, Office 365'),
(64, 22, 'Ethereum Developer Certification', 'Consensys', 'Blockchain smart contracts.', 'Ethereum, Solidity'),
(65, 22, 'Certified Blockchain Developer', 'Blockchain Council', 'Blockchain dev certification.', 'Blockchain, DApps'),
(66, 22, 'IBM Blockchain Foundation Developer', 'IBM', 'Certification for blockchain dev.', 'Hyperledger, Blockchain'),
(67, 23, 'Unity Certified VR Developer', 'Unity', 'Certification in VR dev.', 'Unity, VR, AR'),
(68, 23, 'Unreal AR/VR Developer Certificate', 'Epic Games', 'AR/VR dev certification.', 'Unreal, AR, VR'),
(69, 23, 'Google ARCore Certification', 'Google', 'AR/VR with ARCore.', 'AR, VR, Unity'),
(70, 24, 'IoT Developer Certification', 'IoT Academy', 'Certification in IoT dev.', 'IoT, Sensors'),
(71, 24, 'Cisco IoT Fundamentals', 'Cisco', 'Cisco IoT certificate.', 'IoT, Networking'),
(72, 24, 'Microsoft Azure IoT Developer', 'Microsoft', 'Certification in Azure IoT.', 'IoT, Cloud'),
(73, 25, 'PMP (Project Management Professional)', 'PMI', 'Certification in project management.', 'Project Planning, Agile'),
(74, 25, 'Certified ScrumMaster (CSM)', 'Scrum Alliance', 'Certification in Agile Scrum.', 'Agile, Scrum'),
(75, 25, 'PRINCE2 Foundation', 'AXELOS', 'Project management methodology.', 'PRINCE2, Agile');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `career_id` (`career_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`career_id`) REFERENCES `careers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
