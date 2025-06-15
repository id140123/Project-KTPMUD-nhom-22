CREATE TABLE `role` (
  `Id` int NOT NULL,
  `Name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci

CREATE TABLE `user` (
  `Id` varchar(20) NOT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `RoleId` int DEFAULT NULL,
  `DateOfBirth` date DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Email` (`Email`),
  KEY `FK_User_Role` (`RoleId`),
  CONSTRAINT `FK_User_Role` FOREIGN KEY (`RoleId`) REFERENCES `role` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci

CREATE TABLE `manager` (
  `ManagerId` varchar(20) NOT NULL,
  PRIMARY KEY (`ManagerId`),
  CONSTRAINT `FK_Manager_User` FOREIGN KEY (`ManagerId`) REFERENCES `user` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci

CREATE TABLE `employee` (
  `EmployeeId` varchar(20) NOT NULL,
  `Salary` decimal(10,2) DEFAULT NULL,
  `Workdays` int DEFAULT NULL,
  PRIMARY KEY (`EmployeeId`),
  CONSTRAINT `FK_Employee_User` FOREIGN KEY (`EmployeeId`) REFERENCES `user` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci

CREATE TABLE `violation` (
  `ViolationID` varchar(20) NOT NULL,
  `ViolationName` varchar(100) NOT NULL,
  `Fine` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`ViolationID`),
  UNIQUE KEY `ViolationName` (`ViolationName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci

CREATE TABLE `welfare` (
  `WelfareID` varchar(20) NOT NULL,
  `WelfareName` varchar(100) NOT NULL,
  `Bonus` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`WelfareID`),
  UNIQUE KEY `WelfareName` (`WelfareName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci

CREATE TABLE `employeewelfares` (
  `EmployeeId` varchar(20) NOT NULL,
  `WelfareID` varchar(20) NOT NULL,
  PRIMARY KEY (`EmployeeId`,`WelfareID`),
  KEY `FK_employeewelfares_welfare` (`WelfareID`),
  CONSTRAINT `FK_employeewelfares_employee` FOREIGN KEY (`EmployeeId`) REFERENCES `employee` (`EmployeeId`) ON DELETE CASCADE,
  CONSTRAINT `FK_employeewelfares_welfare` FOREIGN KEY (`WelfareID`) REFERENCES `welfare` (`WelfareID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci

CREATE TABLE `employeeviolations` (
  `EmployeeId` varchar(20) NOT NULL,
  `ViolationID` varchar(20) NOT NULL,
  PRIMARY KEY (`EmployeeId`,`ViolationID`),
  KEY `FK_employeeviolations_violation` (`ViolationID`),
  CONSTRAINT `FK_employeeviolations_employee` FOREIGN KEY (`EmployeeId`) REFERENCES `employee` (`EmployeeId`) ON DELETE CASCADE,
  CONSTRAINT `FK_employeeviolations_violation` FOREIGN KEY (`ViolationID`) REFERENCES `violation` (`ViolationID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci

CREATE TABLE `leaverequest` (
  `LeaveRequestId` int NOT NULL AUTO_INCREMENT,
  `EmployeeId` varchar(20) NOT NULL,
  `Reason` varchar(255) NOT NULL,
  `FormDate` date NOT NULL,
  `ToDate` date NOT NULL,
  `Status` varchar(50) NOT NULL,
  PRIMARY KEY (`LeaveRequestId`),
  KEY `FK_LeaveRequest_Employee` (`EmployeeId`),
  CONSTRAINT `FK_LeaveRequest_Employee` FOREIGN KEY (`EmployeeId`) REFERENCES `employee` (`EmployeeId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
