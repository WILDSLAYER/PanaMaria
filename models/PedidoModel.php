<?php
// models/PedidoModel.php

require_once 'models/BaseModel.php';
require_once 'models/ProductoModel.php';

class PedidoModel extends BaseModel {
    private $productoModel;
    
    public function __construct() {
        parent::__construct();
        $this->table = 'pedidos';
        $this->productoModel = new ProductoModel();
    }
    
    // Registrar nuevo pedido personalizado
    public function registrarPedido($id_usuario, $productos, $fecha_entrega, $anticipo, $metodo_pago) {
        try {
            // Iniciar transacción
            $this->conn->beginTransaction();
            
            // Calcular total del pedido
            $total = 0;
            foreach ($productos as $producto) {
                $info_producto = $this->productoModel->getById($producto['id_producto'], 'id_producto');
                $subtotal = $info_producto['precio'] * $producto['cantidad'];
                $total += $subtotal;
            }
            
            // Calcular saldo pendiente
            $saldo_pendiente = $total - $anticipo;
            
            // Crear pedido
            $pedido_data = [
                'id_usuario' => $id_usuario,
                'fecha_pedido' => date('Y-m-d H:i:s'),
                'fecha_entrega' => $fecha_entrega,
                'estado' => 'pendiente',
                'total' => $total,
                'anticipo' => $anticipo,
                'saldo_pendiente' => $saldo_pendiente,
                'id_metodo_pago' => $metodo_pago
            ];
            
            $id_pedido = $this->create($pedido_data);
            
            if (!$id_pedido) {
                throw new Exception("Error al registrar el pedido");
            }
            
            // Registrar detalle de pedido
            foreach ($productos as $producto) {
                $info_producto = $this->productoModel->getById($producto['id_producto'], 'id_producto');
                $subtotal = $info_producto['precio'] * $producto['cantidad'];
                
                $detalle_data = [
                    'id_pedido' => $id_pedido,
                    'id_producto' => $producto['id_producto'],
                    'cantidad' => $producto['cantidad'],
                    'subtotal' => $subtotal
                ];
                
                $query = "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, subtotal) VALUES (:id_pedido, :id_producto, :cantidad, :subtotal)";
                $stmt = $this->conn->prepare($query);
                
                foreach ($detalle_data as $key => $value) {
                    $stmt->bindValue(':' . $key, $value);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Error al registrar detalle de pedido");
                }
            }
            
            // Confirmar transacción
            $this->conn->commit();
            return $id_pedido;
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->conn->rollBack();
            throw $e;
        }
    }
    
    // Cambiar estado del pedido
    public function cambiarEstado($id_pedido, $estado) {
        $data = ['estado' => $estado];
        return $this->update($id_pedido, $data, 'id_pedido');
    }
    
    // Completar pedido (convertir a venta)
    public function completarPedido($id_pedido, $metodo_pago) {
        try {
            $this->conn->beginTransaction();
            
            // Obtener datos del pedido
            $pedido = $this->getById($id_pedido, 'id_pedido');
            if (!$pedido) {
                throw new Exception("Pedido no encontrado");
            }
            
            // Verificar que el pedido esté pendiente
            if ($pedido['estado'] !== 'pendiente') {
                throw new Exception("Este pedido ya ha sido procesado");
            }
            
            // Obtener detalles del pedido
            $query = "SELECT * FROM detalle_pedido WHERE id_pedido = :id_pedido";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_pedido', $id_pedido);
            $stmt->execute();
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Verificar stock de productos
            foreach ($detalles as $detalle) {
                if (!$this->productoModel->verificarStock($detalle['id_producto'], $detalle['cantidad'])) {
                    throw new Exception("Stock insuficiente para completar el pedido");
                }
            }
            
            // Registrar la venta
            $venta_data = [
                'id_usuario' => $pedido['id_usuario'],
                'fecha' => date('Y-m-d H:i:s'),
                'total' => $pedido['saldo_pendiente'], // Solo se cobra el saldo pendiente
                'id_metodo_pago' => $metodo_pago
            ];
            
            $query = "INSERT INTO ventas (id_usuario, fecha, total, id_metodo_pago) VALUES (:id_usuario, :fecha, :total, :id_metodo_pago)";
            $stmt = $this->conn->prepare($query);
            
            foreach ($venta_data as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error al registrar la venta");
            }
            
            $id_venta = $this->conn->lastInsertId();
            
            // Registrar detalle de venta y actualizar inventario
            foreach ($detalles as $detalle) {
                $detalle_venta = [
                    'id_venta' => $id_venta,
                    'id_producto' => $detalle['id_producto'],
                    'cantidad' => $detalle['cantidad'],
                    'subtotal' => $detalle['subtotal']
                ];
                
                $query = "INSERT INTO detalle_venta (id_venta, id_producto, cantidad, subtotal) VALUES (:id_venta, :id_producto, :cantidad, :subtotal)";
                $stmt = $this->conn->prepare($query);
                
                foreach ($detalle_venta as $key => $value) {
                    $stmt->bindValue(':' . $key, $value);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Error al registrar detalle de venta");
                }
                
                // Actualizar stock
                $cantidad_negativa = $detalle['cantidad'] * -1;
                if (!$this->productoModel->actualizarStock($detalle['id_producto'], $cantidad_negativa)) {
                    throw new Exception("Error al actualizar inventario");
                }
            }
            
            // Cambiar estado del pedido a completado
            $this->cambiarEstado($id_pedido, 'completado');
            
            $this->conn->commit();
            return $id_venta;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
    
    // Obtener pedidos por estado
    public function getPedidosByEstado($estado) {
        $query = "SELECT p.*, u.nombre as cliente 
                 FROM pedidos p 
                 JOIN usuarios u ON p.id_usuario = u.id_usuario 
                 WHERE p.estado = :estado";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estado', $estado);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener pedidos por fecha de entrega
    public function getPedidosByFechaEntrega($fecha) {
        $query = "SELECT p.*, u.nombre as cliente 
                 FROM pedidos p 
                 JOIN usuarios u ON p.id_usuario = u.id_usuario 
                 WHERE DATE(p.fecha_entrega) = :fecha";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener detalle de un pedido
    public function getDetallePedido($id_pedido) {
        $query = "SELECT dp.*, p.nombre, p.precio 
                 FROM detalle_pedido dp 
                 JOIN productos p ON dp.id_producto = p.id_producto 
                 WHERE dp.id_pedido = :id_pedido";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_pedido', $id_pedido);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}