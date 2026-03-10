# BUGS.md — Análisis de código

## Código revisado: `routes/users.js` (Node.js / Express)

Se identificaron **6 problemas** en el fragmento: 3 de seguridad, 1 de lógica, 1 de rendimiento y 1 de manejo de errores.

---

## Bug #1 — Inyección SQL en GET `/users`

**Tipo:** Seguridad crítica

**Línea:**
```js
const query = `SELECT * FROM users WHERE name LIKE '%${search}%'`;
```

**Problema:** El parámetro `search` se interpola directamente en la query sin ninguna sanitización. Un atacante puede inyectar SQL arbitrario y leer, modificar o eliminar datos de la base de datos.

**Ejemplo de ataque:** `search = %' OR '1'='1` devolvería todos los usuarios.

**Corrección:**
```js
const users = await db.query(
  'SELECT * FROM users WHERE name LIKE ?',
  [`%${search}%`]
);
```

---

## Bug #2 — Inyección SQL en POST `/users`

**Tipo:** Seguridad crítica

**Línea:**
```js
`INSERT INTO users (name, email, password) VALUES ('${name}', '${email}', '${password}')`
```

**Problema:** Los tres campos se interpolan directamente. Cualquiera de ellos puede usarse para inyectar SQL y comprometer la base de datos completa.

**Corrección:**
```js
await db.query(
  'INSERT INTO users (name, email, password) VALUES (?, ?, ?)',
  [name, email, hashedPassword]
);
```

---

## Bug #3 — Contraseña guardada en texto plano

**Tipo:** Seguridad crítica

**Línea:**
```js
`INSERT INTO users ... VALUES ('${name}', '${email}', '${password}')`
```

**Problema:** La contraseña se almacena tal como llega del cliente, sin hashear. Si la base de datos es comprometida, todas las contraseñas quedan expuestas directamente. Viola cualquier estándar mínimo de seguridad (OWASP, GDPR, etc.).

**Corrección:**
```js
const bcrypt = require('bcrypt');
const hashedPassword = await bcrypt.hash(password, 10);
```

---

## Bug #4 — DELETE siempre reporta éxito aunque falle

**Tipo:** Lógica

**Línea:**
```js
} catch(e) {
  res.json({ deleted: true });  // siempre reportar éxito
}
```

**Problema:** El bloque `catch` devuelve `{ deleted: true }` incluso cuando la operación falló. El cliente nunca puede saber si el delete funcionó o no. Esto oculta errores reales de base de datos, timeouts o registros inexistentes.

**Corrección:**
```js
} catch(e) {
  console.error(e);
  res.status(500).json({ deleted: false, error: 'Failed to delete user.' });
}
```

---

## Bug #5 — Inyección SQL en DELETE `/users/:id`

**Tipo:** Seguridad crítica

**Línea:**
```js
`DELETE FROM users WHERE id = ${req.params.id}`
```

**Problema:** `req.params.id` puede ser manipulado. Un valor como `1 OR 1=1` eliminaría todos los registros de la tabla.

**Corrección:**
```js
await db.query('DELETE FROM users WHERE id = ?', [req.params.id]);
```

---

## Bug #6 — Sin manejo de errores en GET y POST

**Tipo:** Manejo de errores / robustez

**Líneas:** Handlers de `GET /users` y `POST /users` sin `try/catch`.

**Problema:** Cualquier fallo de base de datos (conexión caída, timeout, violación de constraint) no está capturado. Esto puede crashear el proceso de Node o dejar la petición colgada sin respuesta al cliente.

**Corrección:** Envolver ambos handlers en `try/catch`:
```js
router.get('/users', async (req, res) => {
  try {
    // ...lógica implementada
  } catch (e) {
    console.error(e);
    res.status(500).json({ error: 'Internal server error.' });
  }
});
```

---

## Código corregido completo

```js
const bcrypt = require('bcrypt');

// GET /users
router.get('/users', async (req, res) => {
  try {
    const { search } = req.query;
    const users = await db.query(
      'SELECT * FROM users WHERE name LIKE ?',
      [`%${search}%`]
    );
    res.json(users);
  } catch (e) {
    console.error(e);
    res.status(500).json({ error: 'Internal server error.' });
  }
});

// POST /users
router.post('/users', async (req, res) => {
  try {
    const { name, email, password } = req.body;
    const hashedPassword = await bcrypt.hash(password, 10);
    await db.query(
      'INSERT INTO users (name, email, password) VALUES (?, ?, ?)',
      [name, email, hashedPassword]
    );
    res.status(201).json({ success: true });
  } catch (e) {
    console.error(e);
    res.status(500).json({ error: 'Internal server error.' });
  }
});

// DELETE /users/:id
router.delete('/users/:id', async (req, res) => {
  try {
    await db.query('DELETE FROM users WHERE id = ?', [req.params.id]);
    res.json({ deleted: true });
  } catch (e) {
    console.error(e);
    res.status(500).json({ deleted: false, error: 'Failed to delete user.' });
  }
});
```

---

## Resumen de problemas

| # | Tipo | Severidad | Descripción |
|---|------|-----------|-------------|
| 1 | Seguridad | 🔴 Crítica | SQL Injection en GET `/users` |
| 2 | Seguridad | 🔴 Crítica | SQL Injection en POST `/users` |
| 3 | Seguridad | 🔴 Crítica | Contraseña en texto plano |
| 4 | Lógica | 🟠 Alta | DELETE siempre reporta éxito |
| 5 | Seguridad | 🔴 Crítica | SQL Injection en DELETE `/users/:id` |
| 6 | Robustez | 🟡 Media | Sin manejo de errores en GET y POST |