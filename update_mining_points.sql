-- GOLDNITY 마이닝 포인트를 0.000000003으로 업데이트
UPDATE rewards
SET points_required = 0.000000003
WHERE code = 'mining'
  AND type = 'accumulation'
  AND application_id = (SELECT id FROM applications WHERE name = 'GOLDNITY');

-- 확인
SELECT r.id, a.name as app_name, r.code, r.type, r.points_required
FROM rewards r
LEFT JOIN applications a ON r.application_id = a.id
WHERE r.code = 'mining' AND r.type = 'accumulation';
