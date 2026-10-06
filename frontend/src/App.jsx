import { Navigate, Route, Routes } from 'react-router-dom'

import { AppLayout } from './components/AppLayout'
import { RequireAuth, RequireGuest } from './components/RouteGuards'
import { BookDetailPage } from './features/books/BookDetailPage'
import { BookFormPage } from './features/books/BookFormPage'
import { BookListPage } from './features/books/BookListPage'
import { MasterCrudPage } from './features/masters/MasterCrudPage'
import { MASTERS } from './features/masters/registry'
import { PurchaseListPage } from './features/purchases/PurchaseListPage'
import { PurchaseFormPage } from './features/purchases/PurchaseFormPage'
import { PurchaseDetailPage } from './features/purchases/PurchaseDetailPage'
import { PurchasePaymentPage } from './features/purchases/PurchasePaymentPage'
import { OrderListPage } from './features/purchases/OrderListPage'
import { OrderFormPage } from './features/purchases/OrderFormPage'
import { OrderDetailPage } from './features/purchases/OrderDetailPage'
import { ExpenseListPage } from './features/purchases/ExpenseListPage'
import { ExpenseFormPage } from './features/purchases/ExpenseFormPage'
import { ExpenseDetailPage } from './features/purchases/ExpenseDetailPage'
import { ExpensePaymentPage } from './features/purchases/ExpensePaymentPage'
import { ProvidersPage } from './features/purchases/ProvidersPage'
import { LoginPage } from './pages/LoginPage'
import { NotFoundPage } from './pages/PlaceholderPages'
import { RegisterPage } from './pages/RegisterPage'
import { HOME_PATH } from './lib/routes'

/**
 * Mapa de rutas de la SPA.
 *
 * Todas las vistas se sirven desde React: la navegación nunca abandona el origen
 * del frontend, ni siquiera al iniciar o cerrar sesión.
 */
export default function App() {
  return (
    <Routes>
      <Route
        path="/login"
        element={
          <RequireGuest>
            <LoginPage />
          </RequireGuest>
        }
      />

      <Route
        path="/register"
        element={
          <RequireGuest>
            <RegisterPage />
          </RequireGuest>
        }
      />

      {/* Todo lo que requiere sesión vive dentro del layout de la aplicación. */}
      <Route
        element={
          <RequireAuth>
            <AppLayout />
          </RequireAuth>
        }
      >
        <Route path="/" element={<Navigate to={HOME_PATH} replace />} />
        {/* El dashboard se retiró: la ruta vieja lleva al catálogo. */}
        <Route path="/dashboard" element={<Navigate to={HOME_PATH} replace />} />

        <Route path="/libros" element={<BookListPage />} />
        <Route path="/libros/nuevo" element={<BookFormPage />} />
        <Route path="/libros/:id" element={<BookDetailPage />} />
        <Route path="/libros/:id/editar" element={<BookFormPage />} />

        <Route path="/compras" element={<PurchaseListPage />} />
        <Route path="/compras/nueva" element={<PurchaseFormPage />} />
        <Route path="/compras/:id" element={<PurchaseDetailPage />} />
        <Route path="/compras/:id/pago" element={<PurchasePaymentPage />} />

        <Route path="/ordenes" element={<OrderListPage />} />
        <Route path="/ordenes/nueva" element={<OrderFormPage />} />
        <Route path="/ordenes/:id" element={<OrderDetailPage />} />

        <Route path="/gastos" element={<ExpenseListPage />} />
        <Route path="/gastos/nuevo" element={<ExpenseFormPage />} />
        <Route path="/gastos/:id" element={<ExpenseDetailPage />} />
        <Route path="/gastos/:id/pago" element={<ExpensePaymentPage />} />
        <Route path="/proveedores" element={<ProvidersPage />} />

        {Object.values(MASTERS).map((config) => (
          <Route
            key={config.key}
            path={config.path}
            element={<MasterCrudPage config={config} />}
          />
        ))}
      </Route>

      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  )
}
