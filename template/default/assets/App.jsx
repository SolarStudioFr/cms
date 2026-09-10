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
import useSiteConfig from './useSiteConfig';
import useMenus from './useMenus';
import usePlugins from './usePlugins';
import MenuHook from './MenuHook';
import { TranslationProvider, useTranslator } from './i18n/TranslationContext';
import LanguageSwitcher from './i18n/LanguageSwitcher';

/** Right-hand side of the navbar: login/register links, or the current user + logout once authenticated. */
function AuthNav() {
    const { user, loading, logout } = useAuth();
    const { t } = useTranslator();

    if (loading) {
        return null;
    }

    if (!user) {
        return (
            <Nav>
                <Nav.Link as={Link} to="/login">
                    {t('nav.login')}
                </Nav.Link>
                <Nav.Link as={Link} to="/register">
                    {t('nav.register')}
                </Nav.Link>
            </Nav>
        );
    }

    return (
        <Nav>
            <Nav.Link as={Link} to="/profile">
                {user.email}
            </Nav.Link>
            <Nav.Link onClick={logout}>{t('nav.logout')}</Nav.Link>
        </Nav>
    );
}

function AppShell() {
    const siteConfig = useSiteConfig();
    const menus = useMenus();
    const { enabled: enabledPlugins } = usePlugins();
    const { t } = useTranslator();

    return (
        <BrowserRouter>
            <Navbar bg="light" expand="sm">
                <Container>
                    <Navbar.Brand as={Link} to="/" className="d-flex align-items-center gap-2">
                        {siteConfig?.logoUrl && <img src={siteConfig.logoUrl} alt="" height={32} />}
                        {siteConfig?.siteName ?? 'Solar CMS'}
                    </Navbar.Brand>
                    <Nav className="me-auto">
                        <Nav.Link as={Link} to="/">
                            {t('nav.home')}
                        </Nav.Link>
                        <Nav.Link as={Link} to="/pages">
                            {t('nav.pages')}
                        </Nav.Link>
                        {enabledPlugins.has('portfolio') && (
                            <Nav.Link as={Link} to="/portfolio">
                                {t('nav.portfolio')}
                            </Nav.Link>
                        )}
                        {enabledPlugins.has('news') && (
                            <Nav.Link as={Link} to="/news">
                                {t('nav.news')}
                            </Nav.Link>
                        )}
                        <MenuHook name="header-menu" menus={menus} />
                    </Nav>
                    <LanguageSwitcher className="me-2" />
                    <AuthNav />
                </Container>
            </Navbar>
            <Routes>
                <Route path="/" element={<Home />} />
                <Route
                    path="/pages"
                    element={
                        <Container className="py-4">
                            <h1>{t('nav.pages')}</h1>
                            <PageList />
                        </Container>
                    }
                />
                <Route
                    path="/portfolio"
                    element={
                        <Container className="py-4">
                            <h1>{t('nav.portfolio')}</h1>
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
                            <h1>{t('nav.news')}</h1>
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
                    <MenuHook name="footer-menu" menus={menus} className="mb-2" />
                    {enabledPlugins.has('newsletter') && <NewsletterSignup />}
                </Container>
            </footer>
        </BrowserRouter>
    );
}

export default function App() {
    return (
        <TranslationProvider>
            <AuthProvider>
                <AppShell />
            </AuthProvider>
        </TranslationProvider>
    );
}
