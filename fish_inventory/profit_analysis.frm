TYPE=VIEW
query=select `o`.`id` AS `order_id`,`o`.`order_date` AS `order_date`,`o`.`total_amount` AS `revenue`,sum(`oi`.`quantity_kg` * `pc`.`cost_per_kg`) AS `total_cost`,`o`.`total_amount` - sum(`oi`.`quantity_kg` * `pc`.`cost_per_kg`) AS `profit`,(`o`.`total_amount` - sum(`oi`.`quantity_kg` * `pc`.`cost_per_kg`)) / `o`.`total_amount` * 100 AS `profit_margin` from ((`fish_inventory`.`orders` `o` join `fish_inventory`.`order_items` `oi` on(`o`.`id` = `oi`.`order_id`)) join (select `fish_inventory`.`production_costs`.`fish_type_id` AS `fish_type_id`,`fish_inventory`.`production_costs`.`cost_per_kg` AS `cost_per_kg` from `fish_inventory`.`production_costs` where `fish_inventory`.`production_costs`.`date_recorded` = (select max(`fish_inventory`.`production_costs`.`date_recorded`) from `fish_inventory`.`production_costs`)) `pc` on(`oi`.`fish_type_id` = `pc`.`fish_type_id`)) group by `o`.`id`
md5=58c611a044447c88cb4faaa7c1f13b10
updatable=0
algorithm=0
definer_user=root
definer_host=localhost
suid=1
with_check_option=0
timestamp=0001743258334150373
create-version=2
source=SELECT `o`.`id` AS `order_id`, `o`.`order_date` AS `order_date`, `o`.`total_amount` AS `revenue`, sum(`oi`.`quantity_kg` * `pc`.`cost_per_kg`) AS `total_cost`, `o`.`total_amount`- sum(`oi`.`quantity_kg` * `pc`.`cost_per_kg`) AS `profit`, (`o`.`total_amount` - sum(`oi`.`quantity_kg` * `pc`.`cost_per_kg`)) / `o`.`total_amount` * 100 AS `profit_margin` FROM ((`orders` `o` join `order_items` `oi` on(`o`.`id` = `oi`.`order_id`)) join (select `production_costs`.`fish_type_id` AS `fish_type_id`,`production_costs`.`cost_per_kg` AS `cost_per_kg` from `production_costs` where `production_costs`.`date_recorded` = (select max(`production_costs`.`date_recorded`) from `production_costs`)) `pc` on(`oi`.`fish_type_id` = `pc`.`fish_type_id`)) GROUP BY `o`.`id`
client_cs_name=utf8mb4
connection_cl_name=utf8mb4_general_ci
view_body_utf8=select `o`.`id` AS `order_id`,`o`.`order_date` AS `order_date`,`o`.`total_amount` AS `revenue`,sum(`oi`.`quantity_kg` * `pc`.`cost_per_kg`) AS `total_cost`,`o`.`total_amount` - sum(`oi`.`quantity_kg` * `pc`.`cost_per_kg`) AS `profit`,(`o`.`total_amount` - sum(`oi`.`quantity_kg` * `pc`.`cost_per_kg`)) / `o`.`total_amount` * 100 AS `profit_margin` from ((`fish_inventory`.`orders` `o` join `fish_inventory`.`order_items` `oi` on(`o`.`id` = `oi`.`order_id`)) join (select `fish_inventory`.`production_costs`.`fish_type_id` AS `fish_type_id`,`fish_inventory`.`production_costs`.`cost_per_kg` AS `cost_per_kg` from `fish_inventory`.`production_costs` where `fish_inventory`.`production_costs`.`date_recorded` = (select max(`fish_inventory`.`production_costs`.`date_recorded`) from `fish_inventory`.`production_costs`)) `pc` on(`oi`.`fish_type_id` = `pc`.`fish_type_id`)) group by `o`.`id`
mariadb-version=100432
