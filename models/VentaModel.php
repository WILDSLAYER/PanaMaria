<?php
// models/VentaModel.php

require_once 'models/BaseModel.php';
require_once 'models/ProductoModel.php';

class VentaModel extends BaseModel {
    private $productoModel;
    
    public function __construct() {
        parent::__construct();
        $this->table = 'ventas';
        $this->productoModel = new ProductoModel();
    }
    
    // Registrar una nueva venta
    public function registrarVenta($id_usuario, $productos, $metodo_pago) {
        try {
            // Iniciar transacción
            $this->conn->beginTransaction();
            
            // Calcular total de la venta
            $total = 0;
            foreach ($productos as $producto) {
                $info_producto = $this->productoModel->getById($producto['id_producto'], 'id_producto');
                $subtotal = $info_producto['precio'] * $producto['cantidad'];
                $total += $subtotal;
                
                // Verificar stock
                if (!$this->productoModel->verificarStock($producto['id_producto'], $producto['cantidad'])) {
                    throw new Exception("Stock insuficiente para el producto: " . $info_producto['nombre']);
                }
            }
            
            // Crear venta
            $venta_data = [
                'id_usuario' => $id_usuario,
                'fecha' => date('Y-m-d H:i:s'),
                'total' => $total,
                'id_metodo_pago' => $metodo_pago
            ];
            
            $id_venta = $this->create($venta_data);
            
            if (!$id_venta) {
                throw new Exception("Error al registrar la venta");
            }
            
            // Registrar detalle de venta
            foreach ($productos as $producto) {
                $info_producto = $this->productoModel->getById($producto['id_producto'], 'id_producto');
                $subtotal = $info_producto['precio'] * $producto['cantidad'];
                
                $detalle_data = [
                    'id_venta' => $id_venta,
                    'id_producto' => $producto['id_producto'],
                    'cantidad' => $producto['cantidad'],
                    'subtotal' => $subtotal
                ];
                
                $query = "INSERT INTO detalle_venta (id_venta, id_producto, cantidad, subtotal) VALUES (:id_venta, :id_producto, :cantidad, :subtotal)";
                $stmt = $this->conn->prepare($query);
                
                foreach ($detalle_data as $key => $value) {
                    $stmt->bindValue(':' . $key, $value);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Error al registrar detalle de venta");
                }
                
                // Actualizar stock
                $cantidad_negativa = $producto['cantidad'] * -1; // Convertir a negativo para restar del stock
                if (!$this->productoModel->actualizarStock($producto['id_producto'], $cantidad_negativa)) {
                    throw new Exception("Error al actualizar inventario");
                }
            }
            
            // Confirmar transacción
            $this->conn->commit();
            return $id_venta;
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->conn->rollBack();
            throw $e;
        }
    }
    
    // Obtener ventas por fecha
    public function getVentasByFecha($fecha_inicio, $fecha_fin) {
        $query = "SELECT v.*, u.nombre as usuario, mp.nombre as metodo_pago 
                 FROM ventas v 
                 JOIN usuarios u ON v.id_usuario = u.id_usuario 
                 JOIN metodos_pago mp ON v.id_metodo_pago = mp.id_metodo
                 WHERE DATE(v.fecha) BETWEEN :fecha_inicio AND :fecha_fin";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':fecha_fin', $fecha_fin);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener detalle de una venta
    public function getDetalleVenta($id_venta) {
        $query = "SELECT dv.*, p.nombre, p.precio 
                 FROM detalle_venta dv 
                 JOIN productos p ON dv.id_producto = p.id_producto 
                 WHERE dv.id_venta = :id_venta";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_venta', $id_venta);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener total de ventas por día
    public function getTotalVentasDia($fecha) {
        $query = "SELECT SUM(total) as total FROM ventas WHERE DATE(fecha) = :fecha";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
    
    // Obtener total por método de pago
    public function getTotalPorMetodoPago($fecha) {
        $query = "SELECT mp.nombre, SUM(v.total) as total 
                 FROM ventas v 
                 JOIN metodos_pago mp ON v.id_metodo_pago = mp.id_metodo 
                 WHERE DATE(v.fecha) = :fecha 
                 GROUP BY v.id_metodo_pago";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}