DELIMITER $$
DROP FUNCTION IF EXISTS haversinePt$$

CREATE FUNCTION haversinePt(
        point1 GEOMETRY,
        lat2 FLOAT, lon2 FLOAT
     ) RETURNS FLOAT
    NO SQL DETERMINISTIC
    COMMENT 'Returns the distance kms on the Earth
             between two known points of latitude and longitude
             where the first point is a geospatial object and
             the second is lat/long'
BEGIN
    RETURN DEGREES(ACOS(
              COS(RADIANS(X(point1))) *
              COS(RADIANS(lat2)) *
              COS(RADIANS(lon2) - RADIANS(Y(point1))) +
              SIN(RADIANS(X(point1))) * SIN(RADIANS(lat2))
            )) * 111.045;
END$$

DELIMITER ;



DELIMITER $$
DROP FUNCTION IF EXISTS haversine$$

CREATE FUNCTION haversine(
  lat1 FLOAT, lon1 FLOAT,
  lat2 FLOAT, lon2 FLOAT
) RETURNS FLOAT
NO SQL DETERMINISTIC
  COMMENT 'Returns the distance in kms on the Earth
             between two known points of latitude and longitude'
  BEGIN
    RETURN DEGREES(ACOS(
                       COS(RADIANS(lat1)) *
                       COS(RADIANS(lat2)) *
                       COS(RADIANS(lon2) - RADIANS(lon1)) +
                       SIN(RADIANS(lat1)) * SIN(RADIANS(lat2))
                   )) * 111.045;
  END$$

DELIMITER ;