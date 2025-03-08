<?php
// controllers/ProductoController.php

require_once 'models/ProductoModel.php';

class ProductoController {
    private $productoModel;
    
    public function __construct() {
        $this->productoModel = new ProductoModel();
    }
    
    // Mostrar todos los productos
    public function index() {
        $productos = $this->productoModel->getAll();
        include 'views/productos/index.php';
    }
    
    // Mostrar formulario para agregar producto
    public function agregar() {
        include 'views/productos/agregar.php';
    }
    
    // Procesar formulario de nuevo producto
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = $_POST['nombre'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $precio = $_POST['precio'] ?? 0;
            $stock = $_POST['stock'] ?? 0;
            $categoria = $_POST['categoria'] ?? '';
            
            // Manejar la imagen
            $imagen = 'default.jpg';
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
                $imagen = $this->subirImagen($_FILES['imagen']);
            }
            
            $data = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'precio' => $precio,
                'stock' => $stock,
                'categoria' => $categoria,
                'imagen' => $imagen
            ];
            
            if ($this->productoModel->create($data)) {
                // Si el stock es mayor a 0, registrar en inventario
                if ($stock > 0) {
                    $id_producto = $this->productoModel->conn->lastInsertId();
                    $this->productoModel->actualizarStock($id_producto, $stock);
                }
                
                header('Location: index.php?controller=producto&action=index');
                exit;
            } else {
                $error = "Error al guardar el producto";
                include 'views/productos/agregar.php';
            }
        }
    }
    
    // Mostrar formulario para editar producto
    public function editar() {
        $id = $_GET['id'] ?? 0;
        $producto = $this->productoModel->getById($id, 'id_producto');
        
        if (!$producto) {
            header('Location: index.php?controller=producto&action=index');
            exit;
        }
        
        include 'views/productos/editar.php';
    }
    
    // Procesar actualización de producto
    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id_producto'] ?? 0;
            $nombre = $_POST['nombre'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $precio = $_POST['precio'] ?? 0;
            $categoria = $_POST['categoria'] ?? '';
            
            $data = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'precio' => $precio,
                'categoria' => $categoria
            ];
            
            // Si se sube una nueva imagen
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
                $data['imagen'] = $this->subirImagen($_FILES['imagen']);
            }
            
            if ($this->productoModel->update($id, $data, 'id_producto')) {
                header('Location: index.php?controller=producto&action=index');
                exit;
            } else {
                $error = "Error al actualizar el producto";
                $producto = $this->productoModel->getById($id, 'id_producto');
                include 'views/productos/editar.php';
            }
        }
    }
    
    // Eliminar producto
    public function eliminar() {
        $id = $_GET['id'] ?? 0;
        
        if ($this->productoModel->delete($id, 'id_producto')) {
            header('Location: index.php?controller=producto&action=index');
        } else {
            $error = "Error al eliminar el producto";
            $this->index();
        }
    }
    
    // Gestionar inventario
    public function inventario() {
        $productos = $this->productoModel->getAll();
        include 'views/productos/inventario.php';
    }
    
    // Actualizar inventario
    public function actualizarInventario() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_producto = $_POST['id_producto'] ?? 0;
            $cantidad = $_POST['cantidad'] ?? 0;
            
            if ($this->productoModel->actualizarStock($id_producto, $cantidad)) {
                header('Location: index.php?controller=producto&action=inventario');
                exit;
            } else {
                $error = "Error al actualizar el inventario";
                $this->inventario();
            }
        }
    }
    
    // Método auxiliar para subir imágenes
    private function subirImagen($file) {
        $target_dir = "public/img/productos/";
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return $file_name;
        }
        
        return 'default.jpg';
    }
}