import React from 'react';
import { BrowserRouter, Routes, Route, Link } from 'react-router-dom';
import Container from 'react-bootstrap/Container';
import Nav from 'react-bootstrap/Nav';
import Navbar from 'react-bootstrap/Navbar';
import PageList from './PageList';
import RealisationList from './RealisationList';
import RealisationDetail from './RealisationDetail';

function Home() {
    return (
        <Container className="py-4">
            <h1>Solar CMS</h1>
            <PageList />
        </Container>
    );
}

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
                        <Nav.Link as={Link} to="/realisations">
                            Réalisations
                        </Nav.Link>
                    </Nav>
                </Container>
            </Navbar>
            <Routes>
                <Route path="/" element={<Home />} />
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
            </Routes>
        </BrowserRouter>
    );
}
