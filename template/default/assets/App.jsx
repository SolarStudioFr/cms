import React from 'react';
import { BrowserRouter, Routes, Route, Link } from 'react-router-dom';
import Container from 'react-bootstrap/Container';
import Nav from 'react-bootstrap/Nav';
import Navbar from 'react-bootstrap/Navbar';
import PageList from './PageList';
import PortfolioItemList from './PortfolioItemList';
import PortfolioItemDetail from './PortfolioItemDetail';
import NewsArticleList from './NewsArticleList';
import NewsArticleDetail from './NewsArticleDetail';
import Home from './Home';
import NewsletterSignup from './NewsletterSignup';
import Login from './Login';
import Register from './Register';
import VerifyEmail from './VerifyEmail';
import Profile from './Profile';
import { AuthProvider, useAuth } from './auth/AuthContext';

/** Right-hand side of the navbar: login/register links, or the current user + logout once authenticated. */
function AuthNav() {
    const { user, loading, logout } = useAuth();

    if (loading) {
        return null;
    }

    if (!user) {
        return (
            <Nav>
                <Nav.Link as={Link} to="/login">
                    Connexion
                </Nav.Link>
                <Nav.Link as={Link} to="/register">
                    Inscription
                </Nav.Link>
            </Nav>
        );
    }

    return (
        <Nav>
            <Nav.Link as={Link} to="/profile">
                {user.email}
            </Nav.Link>
            <Nav.Link onClick={logout}>Déconnexion</Nav.Link>
        </Nav>
    );
}

function AppShell() {
    return (
        <BrowserRouter>
            <Navbar bg="light" expand="sm">
                <Container>
                    <Navbar.Brand as={Link} to="/">
                        Solar CMS
                    </Navbar.Brand>
                    <Nav className="me-auto">
                        <Nav.Link as={Link} to="/">
                            Accueil
                        </Nav.Link>
                        <Nav.Link as={Link} to="/pages">
                            Pages
                        </Nav.Link>
                        <Nav.Link as={Link} to="/portfolio">
                            Réalisations
                        </Nav.Link>
                        <Nav.Link as={Link} to="/news">
                            Actualités
                        </Nav.Link>
                    </Nav>
                    <AuthNav />
                </Container>
            </Navbar>
            <Routes>
                <Route path="/" element={<Home />} />
                <Route
                    path="/pages"
                    element={
                        <Container className="py-4">
                            <h1>Pages</h1>
                            <PageList />
                        </Container>
                    }
                />
                <Route
                    path="/portfolio"
                    element={
                        <Container className="py-4">
                            <h1>Réalisations</h1>
                            <PortfolioItemList />
                        </Container>
                    }
                />
                <Route
                    path="/portfolio/:id"
                    element={
                        <Container className="py-4">
                            <PortfolioItemDetail />
                        </Container>
                    }
                />
                <Route
                    path="/news"
                    element={
                        <Container className="py-4">
                            <h1>Actualités</h1>
                            <NewsArticleList />
                        </Container>
                    }
                />
                <Route
                    path="/news/:id"
                    element={
                        <Container className="py-4">
                            <NewsArticleDetail />
                        </Container>
                    }
                />
                <Route path="/login" element={<Login />} />
                <Route path="/register" element={<Register />} />
                <Route path="/verify-email/:token" element={<VerifyEmail />} />
                <Route path="/profile" element={<Profile />} />
            </Routes>
            <footer className="bg-dark text-light py-3 mt-4">
                <Container>
                    <NewsletterSignup />
                </Container>
            </footer>
        </BrowserRouter>
    );
}

export default function App() {
    return (
        <AuthProvider>
            <AppShell />
        </AuthProvider>
    );
}
