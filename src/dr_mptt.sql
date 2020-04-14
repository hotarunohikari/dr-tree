/*
 Navicat Premium Data Transfer
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for web_mptt
-- ----------------------------
DROP TABLE IF EXISTS `dr_mptt`;
CREATE TABLE `dr_mptt`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mid` int(11) NULL DEFAULT NULL COMMENT '会员ID',
  `pid` int(11) NULL DEFAULT NULL COMMENT '上级ID',
  `lft` int(11) NULL DEFAULT NULL COMMENT '左',
  `rht` int(11) NULL DEFAULT NULL COMMENT '右',
  `flr` int(11) NULL DEFAULT NULL COMMENT '层级',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `mid`(`mid`) USING BTREE,
  UNIQUE INDEX `lft`(`lft`) USING BTREE,
  UNIQUE INDEX `rht`(`rht`) USING BTREE,
  INDEX `pid`(`pid`) USING BTREE,
  INDEX `flr`(`flr`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Records of dr_mptt
-- ----------------------------
INSERT INTO `dr_mptt` VALUES (1, 1, 0, 1, 2, 1);

SET FOREIGN_KEY_CHECKS = 1;
