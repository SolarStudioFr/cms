import React from 'react';
import { ButtonGroup, Button } from 'react-bootstrap';
import { AVAILABLE_LOCALES, useTranslator } from './TranslationContext';

/** Small fr/en toggle (step 54), used both on the pre-login screen and inside the admin shell. */
export default function LocaleSwitcher({ className = '' }) {
    const { locale, setLocale } = useTranslator();

    return (
        <ButtonGroup size="sm" className={className}>
            {AVAILABLE_LOCALES.map(({ code, label }) => (
                <Button
                    key={code}
                    variant={code === locale ? 'secondary' : 'outline-secondary'}
                    active={code === locale}
                    onClick={() => setLocale(code)}
                    title={label}
                >
                    {code.toUpperCase()}
                </Button>
            ))}
        </ButtonGroup>
    );
}
