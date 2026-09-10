import React from 'react';
import Nav from 'react-bootstrap/Nav';
import { useTranslator } from './TranslationContext';

/**
 * Public language switcher (step 56): one entry per active site language
 * (step 55) - automatically hidden when there's zero or one, per the step's
 * explicit requirement ("masqué si une seule langue active").
 */
export default function LanguageSwitcher({ className = '' }) {
    const { locale, setLocale, activeLangs } = useTranslator();

    if (activeLangs.length < 2) {
        return null;
    }

    return (
        <Nav className={className}>
            {activeLangs.map((lang) => (
                <Nav.Link
                    key={lang.code}
                    active={lang.code === locale}
                    onClick={() => setLocale(lang.code)}
                    role="button"
                >
                    {lang.code.toUpperCase()}
                </Nav.Link>
            ))}
        </Nav>
    );
}
