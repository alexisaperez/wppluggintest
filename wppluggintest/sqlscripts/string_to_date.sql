#string to date
#---------------
#enrollment_sdate  
#enrollment_edate  
#---------------
#string to int
#---------------
#enrollment_mdate
#---------------
#string to datetime
#---------------
#report_date

#convert string to date:
UPDATE sma34mmm_ncoas_members 
SET report_date = STR_TO_DATE(report_date, '%c/%e/%Y %r'),
    enrollment_sdate = STR_TO_DATE(enrollment_sdate, '%c/%e/%Y'),
    enrollment_edate = STR_TO_DATE(enrollment_sdate, '%c/%e/%Y');

#convert table type:
ALTER TABLE sma34mmm_ncoas_members
MODIFY report_date DATETIME DEFAULT NULL;

ALTER TABLE sma34mmm_ncoas_members
MODIFY enrollment_sdate DATE DEFAULT NULL;

ALTER TABLE sma34mmm_ncoas_members
MODIFY enrollment_edate DATE DEFAULT NULL;

ALTER TABLE sma34mmm_ncoas_members
MODIFY enrollment_mdate INTEGER(2) DEFAULT NULL;