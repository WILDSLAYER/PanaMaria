<?php
// models/ProductoModel.php

require_once 'models/BaseModel.php';

class ProductoModel extends BaseModel {
    
    public function __construct() {
        parent::__construct();
        $this->table = 'productos';
    }
    
    // Obtener productos por categoría
    public function getByCategoria($categoria) {
        $query = "SELECT * FROM " . $this->table . " WHERE categoria = :categoria";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':categoria', $categoria);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener productos con stock bajo
    public function getStockBajo($limite = 10) {
        $query = "SELECT * FROM " . $this->table . " WHERE stock <= :limite";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limite', $limite);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Actualizar stock del producto
    public function actualizarStock($id_producto, $cantidad) {
        $query = "UPDATE " . $this->table . " SET stock = stock + :cantidad WHERE id_producto = :id_producto";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cantidad', $cantidad);
        $stmt->bindParam(':id_producto', $id_producto);
        
        if ($stmt->execute()) {
            // Actualizar también la tabla de inventario
            $query = "INSERT INTO inventario (id_producto, cantidad, fecha_actualizacion) 
                      VALUES (:id_producto, :cantidad, NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_producto', $id_producto);
            $stmt->bindParam(':cantidad', $cantidad);
            
            return $stmt->execute();
        }
        
        return false;
    }
    
    // Verificar si hay stock suficiente
    public function verificarStock($id_producto, $cantidad) {
        $query = "SELECT stock FROM " . $this->table . " WHERE id_producto = :id_producto";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_producto', $id_producto);
        $stmt->execute();
        
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($producto && $producto['stock'] >= $cantidad);
    }
}