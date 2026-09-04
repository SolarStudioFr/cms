import React from 'react';
import { BrowserRouter, Routes, Route, Link } from 'react-router-dom';
import Container from 'react-bootstrap/Container';
import Nav from 'react-bootstrap/Nav';
import Navbar from 'react-bootstrap/Navbar';
import PageList from './PageList';
import RealisationList from './RealisationList';
import RealisationDetail from './RealisationDetail';
import ActualiteList from './ActualiteList';
import ActualiteDetail from './ActualiteDetail';
import Home from './Home';

export default function App() {
    return (
        <BrowserRouter>
            <Navbar bg="light" expand="sm">
                <Container>
                    <Navbar.Brand as={Link} to="/">
                        Solar CMS
                    </Navbar.Brand>
                    <Nav>
                        <Nav.Link as={Link} to="/">
                            Accueil
                        </Nav.Link>
                        <Nav.Link as={Link} to="/pages">
                            Pages
                        </Nav.Link>
                        <Nav.Link as={Link} to="/realisations">
                            Réalisations
                        </Nav.Link>
                        <Nav.Link as={Link} to="/actualites">
                            Actualités
                        </Nav.Link>
                    </Nav>
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
                    path="/realisations"
                    element={
                        <Container className="py-4">
                            <h1>Réalisations</h1>
                            <RealisationList />
                        </Container>
                    }
                />
                <Route
                    path="/realisations/:id"
                    element={
                        <Container className="py-4">
                            <RealisationDetail />
                        </Container>
                    }
                />
                <Route
                    path="/actualites"
                    element={
                        <Container className="py-4">
                            <h1>Actualités</h1>
                            <ActualiteList />
                        </Container>
                    }
                />
                <Route
                    path="/actualites/:id"
                    element={
                        <Container className="py-4">
                            <ActualiteDetail />
                        </Container>
                    }
                />
            </Routes>
        </BrowserRouter>
    );
}
