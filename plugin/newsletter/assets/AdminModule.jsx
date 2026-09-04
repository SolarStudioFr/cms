import CampaignList from './CampaignList';
import CampaignForm from './CampaignForm';
import CampaignSend from './CampaignSend';
import SubscriberList from './SubscriberList';

/**
 * Contract exposed to the admin host via Module Federation, same shape as
 * every other content plugin (see plugin/page/assets/AdminModule.jsx).
 */
export default {
    navItem: {
        label: 'Newsletter',
        path: '/newsletter',
    },
    routes: [
        { path: '/newsletter', element: CampaignList },
        { path: '/newsletter/new', element: CampaignForm },
        { path: '/newsletter/:id/edit', element: CampaignForm },
        { path: '/newsletter/:id/send', element: CampaignSend },
        { path: '/newsletter/subscribers', element: SubscriberList },
    ],
};
