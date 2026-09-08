import CtaModule from './CtaModule';
import DownloadModule from './DownloadModule';
import HeroModule from './HeroModule';
import ImageModule from './ImageModule';
import NewsFeedModule from './NewsFeedModule';
import PortfolioFeedModule from './PortfolioFeedModule';
import ProcessStepsModule from './ProcessStepsModule';
import ServicesGridModule from './ServicesGridModule';
import SliderModule from './SliderModule';
import TextModule from './TextModule';
import TrustedByModule from './TrustedByModule';

/**
 * The builder's module registry (step 10): every block type the canvas can
 * add. Populated incrementally by steps 11-15 (image, slider, download,
 * cta, text) and 40-45 (hero, trusted-by, services grid, process steps,
 * portfolio/news feeds) - each adds one entry here, nothing else in the
 * canvas/renderer needs to change to support a new module type.
 *
 * Each entry: {
 *   type: string,               unique identifier, stored in a block's JSON
 *   label: string,              shown in the "add module" menu
 *   defaultProps: object,       used when a block of this type is added
 *   Edit: React.ComponentType,  ({ props, onChange }) => JSX - admin editor for one block
 *   render: (props) => string,  pure function producing the public HTML for one block
 * }
 */
const registry = [
    ImageModule,
    SliderModule,
    DownloadModule,
    CtaModule,
    TextModule,
    HeroModule,
    TrustedByModule,
    ServicesGridModule,
    ProcessStepsModule,
    PortfolioFeedModule,
    NewsFeedModule,
];

export default registry;

export function getModule(type) {
    return registry.find((module) => module.type === type);
}
