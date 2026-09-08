import React, { useCallback, useEffect, useState } from 'react';
import { Badge, Card, Col, Row, Table } from 'react-bootstrap';
import client from './api/client';

/** Renders a `{value, count}` breakdown list as a small table - shared shape used by every "by X" section below. */
function BreakdownTable({ title, rows, valueLabel }) {
    return (
        <Card className="mb-3">
            <Card.Header>{title}</Card.Header>
            <Table size="sm" className="mb-0">
                <thead>
                    <tr>
                        <th>{valueLabel}</th>
                        <th className="text-end">Vues</th>
                    </tr>
                </thead>
                <tbody>
                    {rows.length === 0 && (
                        <tr>
                            <td colSpan={2} className="text-muted">
                                Aucune donnée
                            </td>
                        </tr>
                    )}
                    {rows.map((row) => (
                        <tr key={String(row.value ?? row.name)}>
                            <td>{row.value ?? row.name ?? 'Inconnu'}</td>
                            <td className="text-end">{row.count}</td>
                        </tr>
                    ))}
                </tbody>
            </Table>
        </Card>
    );
}

function formatSeconds(value) {
    if (value === null || value === undefined) {
        return '—';
    }
    return `${Math.round(value)} s`;
}

function formatPercent(value) {
    if (value === null || value === undefined) {
        return '—';
    }
    return `${Math.round(value)} %`;
}

/**
 * Admin dashboard for the Stats plugin (step 36): fetches the single
 * aggregated summary from StatsDashboardController and renders it as a
 * handful of cards/tables - no client-side date picker yet (the backend
 * already accepts ?from=&to=, left for a later iteration since nothing in
 * the roadmap asked for a specific range UI).
 */
export default function Dashboard() {
    const [summary, setSummary] = useState(null);
    const [loading, setLoading] = useState(true);

    const load = useCallback(() => {
        setLoading(true);
        client
            .get('/admin/stats/summary')
            .then(({ data }) => setSummary(data))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    if (loading || !summary) {
        return <p>Chargement...</p>;
    }

    return (
        <div>
            <h1 className="mb-4">Statistiques</h1>

            <Row className="mb-3">
                <Col md={4}>
                    <Card body className="text-center">
                        <div className="fs-3">{summary.totalPageViews}</div>
                        <div className="text-muted">Pages vues</div>
                    </Card>
                </Col>
                <Col md={4}>
                    <Card body className="text-center">
                        <div className="fs-3">{formatSeconds(summary.avgTimeOnPageSeconds)}</div>
                        <div className="text-muted">Temps moyen par page</div>
                    </Card>
                </Col>
                <Col md={4}>
                    <Card body className="text-center">
                        <div className="fs-3">{formatPercent(summary.avgScrollPercent)}</div>
                        <div className="text-muted">Scroll moyen</div>
                    </Card>
                </Col>
            </Row>

            <Row className="mb-3">
                <Col md={4}>
                    <Card body className="text-center">
                        <div className="fs-3">{summary.botVsHuman.human}</div>
                        <div className="text-muted">Visites humaines</div>
                    </Card>
                </Col>
                <Col md={4}>
                    <Card body className="text-center">
                        <div className="fs-3">{summary.botVsHuman.bot}</div>
                        <div className="text-muted">Visites de robots</div>
                    </Card>
                </Col>
            </Row>

            <Card className="mb-3">
                <Card.Header>Pages les plus vues</Card.Header>
                <Table size="sm" className="mb-0">
                    <thead>
                        <tr>
                            <th>URL</th>
                            <th className="text-end">Vues</th>
                            <th className="text-end">Temps moyen</th>
                        </tr>
                    </thead>
                    <tbody>
                        {summary.topPages.length === 0 && (
                            <tr>
                                <td colSpan={3} className="text-muted">
                                    Aucune donnée
                                </td>
                            </tr>
                        )}
                        {summary.topPages.map((page) => (
                            <tr key={page.url}>
                                <td>{page.url}</td>
                                <td className="text-end">{page.views}</td>
                                <td className="text-end">{formatSeconds(page.avgTimeOnPageSeconds)}</td>
                            </tr>
                        ))}
                    </tbody>
                </Table>
            </Card>

            <Row>
                <Col md={4}>
                    <BreakdownTable title="Navigateurs" rows={summary.browsers} valueLabel="Navigateur" />
                </Col>
                <Col md={4}>
                    <BreakdownTable title="Systèmes d'exploitation" rows={summary.operatingSystems} valueLabel="OS" />
                </Col>
                <Col md={4}>
                    <BreakdownTable title="Appareils" rows={summary.deviceTypes} valueLabel="Type" />
                </Col>
                <Col md={4}>
                    <BreakdownTable title="Langues" rows={summary.languages} valueLabel="Langue" />
                </Col>
                <Col md={4}>
                    <BreakdownTable title="Pays" rows={summary.countries} valueLabel="Pays" />
                </Col>
                <Col md={4}>
                    <BreakdownTable title="Robots détectés" rows={summary.topBots} valueLabel="Robot" />
                </Col>
            </Row>

            <Card>
                <Card.Header>Clics les plus fréquents</Card.Header>
                <Table size="sm" className="mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Libellé</th>
                            <th>Cible</th>
                            <th className="text-end">Clics</th>
                        </tr>
                    </thead>
                    <tbody>
                        {summary.topClicks.length === 0 && (
                            <tr>
                                <td colSpan={4} className="text-muted">
                                    Aucune donnée
                                </td>
                            </tr>
                        )}
                        {summary.topClicks.map((click, index) => (
                            <tr key={`${click.elementType}-${click.label}-${click.targetUrl}-${index}`}>
                                <td>
                                    <Badge bg={click.elementType === 'link' ? 'info' : 'secondary'}>{click.elementType}</Badge>
                                </td>
                                <td>{click.label ?? '—'}</td>
                                <td>{click.targetUrl ?? '—'}</td>
                                <td className="text-end">{click.count}</td>
                            </tr>
                        ))}
                    </tbody>
                </Table>
            </Card>
        </div>
    );
}
