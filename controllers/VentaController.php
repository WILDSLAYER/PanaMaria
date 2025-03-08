<?php
// controllers/VentaController.php

require_once 'models/VentaModel.php';
require_once 'models/ProductoModel.php';
require_once 'models/UsuarioModel.php';

class VentaController {
    private $ventaModel;
    private $productoModel;
    private $usuarioModel;
    
    public function __construct() {
        $this->ventaModel = new VentaModel();
        $this->productoModel = new ProductoModel();
        $this->usuarioModel = new UsuarioModel();
    }
    
    // Listar ventas
    public function index() {
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
        
        $ventas = $this->ventaModel->getVentasByFecha($fecha_inicio, $fecha_fin);
        include 'views/ventas/index.php';
    }
    
    // Mostrar formulario para nueva venta
    public function nueva() {
        $productos = $this->productoModel->getAll();
        $clientes = $this->usuarioModel->getClientes();
        include 'views/ventas/nueva.php';
    }
    
    // Procesar nueva venta
    public function procesar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id_usuario = $_POST['id_usuario'] ?? 1; // Cliente por defecto o anónimo
                $metodo_pago = $_POST['metodo_pago'] ?? 1; // Efectivo por defecto
                
                // Obtener productos de la venta
                $productos = [];
                foreach ($_POST['productos'] as $key => $id_producto) {
                    if ($id_producto && isset($_POST['cantidades'][$key]) && $_POST['cantidades'][$key] > 0) {
                        $productos[] = [
                            'id_producto' => $id_producto,
                            'cantidad' => $_POST['cantidades'][$key]
                        ];
                    }
                }
                
                if (empty($productos)) {
                    throw new Exception("Debe seleccionar al menos un producto");
                }
                
                $id_venta = $this->ventaModel->registrarVenta($id_usuario, $productos, $metodo_pago);
                
                if ($id_venta) {
                    $_SESSION['success'] = "Venta registrada correctamente";
                    header('Location: index.php?controller=venta&action=detalle&id=' . $id_venta);
                    exit;
                } else {
                    throw new Exception("Error al registrar la venta");
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: index.php?controller=venta&action=nueva');
                exit;
            }
        }
    }
    
    // Ver detalle de venta
    public function detalle() {
        $id_venta = $_GET['id'] ?? 0;
        $venta = $this->ventaModel->getById($id_venta, 'id_venta');
        
        if (!$venta) {
            $_SESSION['error'] = "Venta no encontrada";
            header('Location: index.php?controller=venta&action=index');
            exit;
        }
        
        $detalles = $this->ventaModel->getDetalleVenta($id_venta);
        $cliente = $this->usuarioModel->getById($venta['id_usuario'], 'id_usuario');
        
        include 'views/ventas/detalle.php';
    }
    
    // Generar ticket de venta
    public function ticket() {
        $id_venta = $_GET['id'] ?? 0;
        $venta = $this->ventaModel->getById($id_venta, 'id_venta');
        
        if (!$venta) {
            $_SESSION['error'] = "Venta no encontrada";
            header('Location: index.php?controller=venta&action=index');
            exit;
        }
        
        $detalles = $this->ventaModel->getDetalleVenta($id_venta);
        $cliente = $this->usuarioModel->getById($venta['id_usuario'], 'id_usuario');
        
        // Vista específica para ticket
        include 'views/ventas/ticket.php';
    }
    
    // Buscar producto por AJAX
    public function buscarProducto() {
        if (isset($_GET['term'])) {
            $term = $_GET['term'];
            $query = "SELECT id_producto, nombre, precio, stock FROM productos WHERE nombre LIKE :term AND stock > 0";
            $stmt = $this->productoModel->conn->prepare($query);
            $stmt->bindValue(':term', "%$term%");
            $stmt->execute();
            
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($productos);
            exit;
        }
    }
}