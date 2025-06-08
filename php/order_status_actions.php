<?php
// This would be included in your restaurant.php file to show appropriate action buttons

function getStatusActions($order_id, $current_status) {
    $actions = [];
    
    switch ($current_status) {
        case 'Pending':
            $actions[] = [
                'url' => "update_order_status.php?id=$order_id&status=Preparing",
                'class' => 'btn-info',
                'icon' => 'fa-play',
                'text' => 'Start Preparing'
            ];
            $actions[] = [
                'url' => "update_order_status.php?id=$order_id&status=Cancelled",
                'class' => 'btn-danger',
                'icon' => 'fa-times',
                'text' => 'Cancel Order'
            ];
            break;
            
        case 'Preparing':
            $actions[] = [
                'url' => "update_order_status.php?id=$order_id&status=Ready",
                'class' => 'btn-success',
                'icon' => 'fa-check',
                'text' => 'Mark as Ready'
            ];
            $actions[] = [
                'url' => "update_order_status.php?id=$order_id&status=Cancelled",
                'class' => 'btn-danger',
                'icon' => 'fa-times',
                'text' => 'Cancel Order'
            ];
            break;
            
        case 'Ready':
            // System will auto-assign delivery person when marked ready
            $actions[] = [
                'url' => "#",
                'class' => 'btn-secondary',
                'icon' => 'fa-clock',
                'text' => 'Waiting for Delivery'
            ];
            break;
            
        case 'Out for Delivery':
            $actions[] = [
                'url' => "#",
                'class' => 'btn-secondary',
                'icon' => 'fa-truck',
                'text' => 'In Transit'
            ];
            break;
            
        case 'Delivered':
            $actions[] = [
                'url' => "#",
                'class' => 'btn-success',
                'icon' => 'fa-check-circle',
                'text' => 'Delivered'
            ];
            break;
            
        case 'Cancelled':
            $actions[] = [
                'url' => "#",
                'class' => 'btn-danger',
                'icon' => 'fa-ban',
                'text' => 'Cancelled'
            ];
            break;
    }
    
    return $actions;
}
?>