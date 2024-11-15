<?php
//conexión con base de datos ----------------------------------------------------------------

    function conectar($host, $usuario, $password, $base_datos){
        $conexion = new mysqli($host, $usuario, $password, $base_datos);
        $conexion->set_charset("utf8");
        return $conexion;
    }

//función para cabecera ----------------------------------------------------------------

    function dibujarCabecera($ruta_i, $ruta_soc, $ruta_serv, $ruta_tes, $ruta_not, $ruta_cit){
        $resultado='';
        $resultado.="
        <header>
        <h1><a href='$ruta_i'>糸東流</a></h1>
            <nav>
                <ul>
                    <li><a href='$ruta_soc'>SOCIOS</a></li>
                    <li><a href='$ruta_serv'>SERVICIOS</a></li>
                    <li><a href='$ruta_tes'>TESTIMONIOS</a></li>
                    <li><a href='$ruta_not'>NOTICIAS</a></li>
                    <li><a href='$ruta_cit'>CITAS</a></li>
                </ul>
            </nav>
        </header>";

        return $resultado;
    }

//función para paginado ----------------------------------------------------------------

    function imprimirPaginado($conexion, $pagina){
        $resultado='<ul>';
        $total='';
        $sql='SELECT COUNT(id) AS total FROM noticia';
        $sql_result=$conexion->query($sql);

        while($row=$sql_result->fetch_array(MYSQLI_ASSOC)){
            $total=$row["total"];
        }

        $total = intval($total); // Convertir a entero en PHP
        $num_paginas=round($total/4); //ceil redondea siempre hacia arriba, floor hacia abajo

        for($i=1; $i<=$num_paginas; $i++){
            if($pagina == $i){
                $resultado.="<a href='noticias.php?pagina=$i'><li class='marcado'>$i</li></a>"; //si es el de la página actual, lo marco
            }else{
                $resultado.="<a href='noticias.php?pagina=$i'><li>$i</li></a>"; //imprimo un li por cada 4 noticias
            }
        }
        $resultado.='</ul>';

        return $resultado;
    }

//funciones index ----------------------------------------------------------------

    function testimonioRandom($conexion){
        $resultado='';
        $sql='SELECT contenido,nombre FROM testimonio
            JOIN socio ON socio.id=testimonio.autor
            ORDER BY RAND() LIMIT 1
        ';

        $sql_result=$conexion->query($sql);
        while($row=$sql_result->fetch_array(MYSQLI_ASSOC)){
            $testimonio=$row["contenido"];
            $nombre_usuario=$row["nombre"];
            $resultado.="
                <h3>$nombre_usuario</h3>
                <p><em>$testimonio</em></p>
            ";
        }
        return $resultado;
    }

    function ultimasNoticias($conexion){
        $sql='SELECT id,titulo,contenido,imagen,fecha_publicacion
        FROM noticia 
        WHERE fecha_publicacion <= CURDATE()
        ORDER BY fecha_publicacion DESC LIMIT 3';
        $ruta_index=true;

        $resultado=generarListaNoticias($sql, $conexion, $ruta_index);

        return $resultado;
    }

//funciones noticias ----------------------------------------------------------------

    function imprimirNoticias($conexion, $pagina){
        $offset = ($pagina - 1) * 4; //para que calcule el offset de 4 en 4 según el número de página (las noticias de cada página)
        $sql="SELECT id,titulo,contenido,imagen,fecha_publicacion
        FROM noticia 
        WHERE fecha_publicacion <= CURDATE()
        ORDER BY fecha_publicacion DESC LIMIT 4 OFFSET $offset";
        $ruta_index=false;

        $resultado=generarListaNoticias($sql, $conexion, $ruta_index);

        return $resultado;
    }

    function generarNoticia($conexion, $id){
        $resultado='';
        $sql="SELECT * FROM noticia WHERE id=$id";

        $sql_result=$conexion->query($sql);
        while($row=$sql_result->fetch_array(MYSQLI_ASSOC)){
            $titulo=$row["titulo"];
            $contenido=$row["contenido"];
            $imagen=$row["imagen"];
            $fecha_publicacion=$row["fecha_publicacion"];
            $resultado.="
                <h1>$titulo</h1>
                <img src='$imagen'>
                <p>$contenido</p>
                <p>$fecha_publicacion</p>
            ";
        }

        return $resultado;
    }

    function añadirNoticia($conexion, $imagen, $titulo, $contenido, $fecha){
        $resultado='';
        $sql='INSERT INTO noticia (titulo, contenido, imagen, fecha_publicacion)
            VALUES (?, ?, ?, ?)';
    
        $consulta=$conexion->prepare($sql);
        $consulta->bind_param("ssss", $titulo, $contenido, $imagen, $fecha);
        $consulta->execute();

        if($consulta){
            $resultado.="<h1 class='centrado'>Noticia publicada</h1>
            <h2 class='centrado'>Volviendo a la página de noticias en 3 segundos...</h2>";
        }else{
            $resultado.="<h1 class='centrado'>Error</h1>";
        }
        return $resultado;
        $consulta->close();
    }

//funciones socios ----------------------------------------------------------------

    function imprimirSocios($conexion){
        $resultado='';
        $sql='SELECT id,nombre,usuario,edad,telefono,foto FROM socio';

        $sql_result=$conexion->query($sql);
        while($row=$sql_result->fetch_array(MYSQLI_ASSOC)){
            $id=$row["id"];
            $nombre=$row["nombre"];
            $usuario=$row["usuario"];
            $edad=$row["edad"];
            $tlfn=$row["telefono"];
            $ruta_avatar=$row["foto"];

            $resultado.="
                <div class='tarjeta_socio'>
                    <div class='avatar'><img src='$ruta_avatar'></div>
                    <h3>$nombre</h3>
                    <p>Usuario: $usuario</p>
                    <p>Edad: $edad</p>
                    <p>Tlfn: $tlfn</p>
                    <a href='socios-mod.php?id=$id' class='boton'>Modificar</a>
                </div>
            ";
        }
        return $resultado;
    }


    function imprimirModificarSocio($conexion, $id){
        $resultado='';
        $sql='SELECT nombre,usuario,edad,telefono,foto FROM socio WHERE id=?';

        $consulta=$conexion->prepare($sql);
        $consulta->bind_param("i", $id);
        $consulta->execute();
        $consulta->bind_result($nombre_r, $usuario_r, $edad_r, $telefono_r, $foto_r);

        while($consulta->fetch()){
            $resultado.="<div class='tarjeta_socio'>
                <form action='socios-confirm.php' method='post' id='formulario-mod'>
                    <div class='avatar'><img src='$foto_r'></div>
                    <input type='text' value='$nombre_r' name='nombre' placeholder='Nombre completo' id='nombre-mod'>
                    <span class='error'></span>
                    <input type='text' value='$usuario_r' name='user' placeholder='Nombre de usuario' id='user-mod'>
                    <span class='error'></span>
                    <input type='text' value='$edad_r' name='edad' placeholder='Edad' id='edad-mod'>
                    <span class='error'></span>
                    <input type='text' value='$telefono_r' name='tlfn' placeholder='Teléfono' id='tlfn-mod'>
                    <span class='error'></span>
                    <input type='hidden' value='$id' name='id'>
                    <button type='submit'>Actualizar socio</button>
                </form>
            </div>
            ";
        }

        $consulta->close();
        return $resultado;
    }


    function añadirSocio($conexion, $nombre, $edad, $pass, $usuario, $tlfn, $ruta_img){
        $sql='INSERT INTO socio (nombre, edad, pass, usuario, telefono, foto) 
        VALUES (?, ?, ?, ?, ?, ?)';
        $consulta=$conexion->prepare($sql);
        $consulta->bind_param("sissss", $nombre, $edad, $pass, $usuario, $tlfn, $ruta_img);
        $consulta->execute();
        $consulta->close();
    }


    function actualizarSocio($conexion, $id, $nombre, $usuario, $edad, $telefono){
        $resultado="";
        $sql='UPDATE socio SET nombre=?, usuario=?, edad=?, telefono=? WHERE id=?';
        $consulta=$conexion->prepare($sql);
        $consulta->bind_param("ssiii", $nombre, $usuario, $edad, $telefono, $id);
        $consulta->execute();

        if($consulta){
            $resultado.="<h1 class='centrado'>Usuario actualizado</h1>
            <h2 class='centrado'>Volviendo a la página de socios en 3 segundos...</h2>";
        }else{
            $resultado.="<h1 class='centrado'>Error</h1>";
        }

        $consulta->close();
        return $resultado;
    }

//funciones testimonios ----------------------------------------------------------------

function imprimirTestimonios($conexion){
    $resultado='';
        $sql='SELECT testimonio.id,nombre,contenido,fecha FROM socio
        JOIN testimonio ON socio.id=testimonio.autor
        ORDER BY fecha DESC';

        $sql_result=$conexion->query($sql);
        while($row=$sql_result->fetch_array(MYSQLI_ASSOC)){
            $nombre=$row["nombre"];
            $texto=$row["contenido"];
            $fecha=$row["fecha"];

            $resultado.="
                <div class='card-testimonio'>
                    <h2>$nombre</h2>
                    <p>$texto</p>
                    <p>$fecha</p>
                </div>
            ";
        }
        return $resultado;
}

function añadirTestimonio($conexion, $autor, $contenido){
    $fecha_actual = date("Y-m-d");
    $sql='INSERT INTO testimonio (autor, contenido, fecha)
        VALUES (?, ?, ?)';

    $consulta=$conexion->prepare($sql);
    $consulta->bind_param("iss", $autor, $contenido, $fecha_actual);
    $consulta->execute();
    $consulta->close();
}

//funciones servicios ----------------------------------------------------------------

function imprimirServicios($conexion){
        $resultado='';
        $sql='SELECT id, descripcion, duracion, unidad_duracion, precio FROM servicio
        ORDER BY descripcion ASC';

        $sql_result=$conexion->query($sql);
        while($row=$sql_result->fetch_array(MYSQLI_ASSOC)){
            $id=$row["id"];
            $descripcion=$row["descripcion"];
            $duracion=$row["duracion"];
            $ud_duracion=$row["unidad_duracion"];
            $precio=$row["precio"];

            $resultado.="
                <div class='p-5 text-center bg-body-secondary rounded-4 custom-serv'>
                    <h1 class='text-body-emphasis'>$descripcion</h1>
                    <p class='lead'>Este servicio cuenta con una duración de <span>$duracion $ud_duracion</span> y un precio de <span>$precio Euros</span>.</p>
                    <a href='servicios-mod.php?id=$id'>
                        <button class='btn btn-primary d-inline-flex align-items-center btn-custom'>
                            Modificar
                        </button>
                    </a>
                </div>
            ";
        }
        return $resultado;
}

function imprimirModificarServicio($conexion, $id){
    $resultado='';
    $sql='SELECT descripcion, duracion, precio FROM servicio
    WHERE id=?';

    $consulta=$conexion->prepare($sql);
    $consulta->bind_param("i", $id);
    $consulta->execute();
    $consulta->bind_result($descripcion_r, $duracion_r, $precio_r);

    while($consulta->fetch()){
        $resultado.="
        <div class='card-servicio'>
            <form action='servicios-confirm.php' method='post' id='formulario-servicios'>
                    <textarea name='contenido-serv' id='contenido-servicio' placeholder='Descripción del servicio'>$descripcion_r</textarea>
                    <span class='error'></span>
                    <input type='text' name='duracion' id='duracion-servicio' value='$duracion_r' placeholder='Duración'>
                    <span class='error'></span>
                    <select name='u-duracion' id='u-duracion-servicio'>
                        <option value=''>Selecciona una unidad</option>
                        <option value='minutos'>Minutos</option>
                        <option value='horas'>Horas</option>
                    </select>
                    <span class='error'></span>
                    <input type='text' name='precio' id='precio-servicio' value='$precio_r' placeholder='Precio'>
                    <span class='error'></span>
                    <input name='id' type='hidden' value='$id'>
                    <button class='btn btn-outline-secondary' type='submit'>Actualizar servicio</button>
            </form>
        </div>
        ";
    }

    $consulta->close();
    return $resultado;
}

function añadirServicio($conexion, $descripcion, $duracion, $ud_duracion, $precio){
    $sql='INSERT INTO servicio (descripcion, duracion, unidad_duracion, precio)
        VALUES (?, ?, ?, ?)';

    $consulta=$conexion->prepare($sql);
    $consulta->bind_param("sisi", $descripcion, $duracion, $ud_duracion, $precio);
    $consulta->execute();
    $consulta->close();
}

function actualizarServicio($conexion, $id, $descripcion, $duracion, $ud_duracion, $precio){
    $resultado="";
    $sql='UPDATE servicio SET descripcion=?, duracion=?, unidad_duracion=?, precio=? WHERE id=?';
    $consulta=$conexion->prepare($sql);
    $consulta->bind_param("sisii", $descripcion, $duracion, $ud_duracion, $precio, $id);
    $consulta->execute();

    if($consulta){
        $resultado.="<h1 class='centrado'>Servicio actualizado</h1>
        <h2 class='centrado'>Volviendo a la página de servicios en 3 segundos...</h2>";
    }else{
        $resultado.="<h1 class='centrado'>Error</h1>";
    }

    $consulta->close();
    return $resultado;
}

//funciones internas ----------------------------------------------------------------
    
    function generarListaNoticias($sql, $conexion, $ruta_index){
        $sql_result=$conexion->query($sql);
        $resultado='';

        while($row=$sql_result->fetch_array(MYSQLI_ASSOC)){
            $id=$row["id"];
            $titulo=$row["titulo"];
            $contenido=substr($row["contenido"], 0, 170);
            $ruta_imagen=$row["imagen"];
            $fecha=$row["fecha_publicacion"];
            $enlace="noticia-comp.php?id=$id";
            
            //compruebo si estoy en el index para cambiar las rutas
            if($ruta_index){
                $ruta_imagen=str_replace('../pics/', './pics/', $ruta_imagen);
                $enlace='./paginas/'.$enlace;
            }

            $resultado.="
            <a href='$enlace'>
                <article class='noticia' data-id='$id'>
                    <h2>$titulo</h2>
                    <div class='contenido-noticia'>
                        <div class='img-noticia'>
                            <img src='$ruta_imagen'>
                        </div>
                        <div class='side-text'>
                            <p>$contenido...</p>
                            <p>$fecha</p>
                        </div>
                    </div>
                </article>
            </a>";
        }

        return $resultado;
    }
?>