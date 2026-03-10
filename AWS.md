# AWS.md — Almacenamiento de imágenes de productos

## 1. ¿Qué servicios usaría y por qué?

**Amazon S3** como almacenamiento principal de imágenes.

S3 es la opción natural para este caso por varias razones: es virtualmente ilimitado en capacidad, tiene una durabilidad del 99.999999999% (11 nueves), permite servir archivos públicamente por URL directa, y se integra de forma nativa con el SDK de PHP que usa Laravel.

Opcionalmente agregaría **Amazon CloudFront** (CDN) delante del bucket para reducir la latencia de entrega a usuarios en distintas regiones y reducir los costos de transferencia saliente desde S3.

Para los permisos, usaría **IAM Roles** asignados a la instancia EC2 — sin credenciales hardcodeadas en el código.

---

## 2. Política de bucket

El bucket tiene **Block Public Access activado por defecto**. Solo se permite lectura pública sobre la carpeta `images/` mediante una Bucket Policy explícita. La escritura nunca es pública — solo la EC2 puede subir archivos a través del IAM Role.

**Bucket Policy — lectura pública de imágenes:**
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "PublicReadImages",
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::my-products-bucket/images/*"
    }
  ]
}
```

**IAM Policy para la EC2 — solo puede subir y eliminar:**
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "EC2UploadOnly",
      "Effect": "Allow",
      "Action": [
        "s3:PutObject",
        "s3:DeleteObject"
      ],
      "Resource": "arn:aws:s3:::my-products-bucket/images/*"
    }
  ]
}
```

Con esta configuración:
- Cualquier persona puede **ver** las imágenes por URL pública.
- Solo la EC2 puede **subir o eliminar** archivos.
- Nadie en internet puede **listar** el contenido del bucket ni escribir directamente.

---

## 3. Presigned URLs para subida segura desde el cliente

El cliente nunca recibe credenciales de AWS. El flujo es el siguiente:

```
Cliente → POST /api/products/upload-url → EC2 genera presigned URL → Cliente sube directo a S3
```

**Paso a paso:**

1. El cliente solicita al backend una URL de subida.
2. El backend genera una **presigned URL** con expiración corta (5 minutos).
3. El cliente sube la imagen directamente a S3 con un `PUT` HTTP a esa URL.
4. La imagen queda almacenada. El backend guarda la key/URL final en la base de datos.

**Implementación en Laravel (AWS SDK v3):**
```php
use Aws\S3\S3Client;

$s3 = new S3Client([
    'region'  => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'version' => 'latest',
]);

$key = 'images/products/' . uniqid() . '.jpg';

$cmd = $s3->getCommand('PutObject', [
    'Bucket'      => env('AWS_BUCKET'),
    'Key'         => $key,
    'ContentType' => 'image/jpeg',
]);

$presignedUrl = (string) $s3->createPresignedRequest($cmd, '+5 minutes')->getUri();

return response()->json([
    'upload_url' => $presignedUrl,
    'public_url' => "https://" . env('AWS_BUCKET') . ".s3.amazonaws.com/" . $key,
]);
```

**Ventajas de este enfoque:**
- Las credenciales AWS nunca salen del servidor.
- El tráfico de la imagen no pasa por la EC2, reduciendo carga y costos de transferencia.
- La URL expira automáticamente — no se puede reutilizar.

---

## 4. Consideraciones de costos

**Rubros principales de S3:**
- **Almacenamiento:** ~$0.023 USD por GB/mes en S3 Standard.
- **Solicitudes:** PUT/POST cuestan ~$0.005 por 1,000 requests. GET es más barato (~$0.0004).
- **Transferencia saliente:** el costo más significativo — $0.09 por GB transferido a internet. Aquí es donde CloudFront ayuda más.

**Estrategias para reducir costos:**
- **CloudFront como CDN:** cachea las imágenes en edge locations, reduciendo las solicitudes directas a S3 y el costo de transferencia saliente hasta en un 60-70%.
- **S3 Intelligent-Tiering:** mueve automáticamente imágenes poco accedidas a clases de almacenamiento más baratas (hasta 40% de ahorro).
- **Lifecycle Policy:** elimina o archiva en Glacier imágenes huérfanas (productos eliminados) después de N días, evitando pagar por almacenamiento innecesario.
- **Compresión antes de subir:** reducir el tamaño de las imágenes en el cliente antes del upload disminuye costos de almacenamiento y transferencia.