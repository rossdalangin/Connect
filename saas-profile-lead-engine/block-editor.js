const { registerBlockType } = wp.blocks;
const { SelectControl } = wp.components;
const { withSelect } = wp.data;

registerBlockType('saas/profile-embed', {
    title: 'SaaS Profile Embed',
    icon: 'id-alt',
    category: 'widgets',
    attributes: {
        profile_id: { type: 'number', default: 0 }
    },
    edit: withSelect((select) => {
        return {
            profiles: select('core').getEntityRecords('postType', 'saas_profile')
        };
    })(({ profiles, attributes, setAttributes }) => {
        if (!profiles) return 'Loading Profiles...';
        if (profiles.length === 0) return 'No profiles found.';

        const options = profiles.map(p => ({ label: p.title.rendered, value: p.id }));

        return (
            <div style={{ padding: '20px', border: '1px solid #7551FF', borderRadius: '8px' }}>
                <h4>SaaS Profile Embed</h4>
                <SelectControl
                    label="Select Profile"
                    value={attributes.profile_id}
                    options={[{ label: 'Select a profile...', value: 0 }, ...options]}
                    onChange={(val) => setAttributes({ profile_id: parseInt(val) })}
                />
            </div>
        );
    }),
    save: () => null // Handled by PHP render_callback
});

registerBlockType('saas/lead-form-block', {
    title: 'SaaS Lead Capture Form',
    icon: 'email-alt',
    category: 'widgets',
    attributes: {
        profile_id: { type: 'number', default: 0 },
        title: { type: 'string', default: 'Contact Me' }
    },
    edit: withSelect((select) => {
        return {
            profiles: select('core').getEntityRecords('postType', 'saas_profile')
        };
    })(({ profiles, attributes, setAttributes }) => {
        if (!profiles) return 'Loading Profiles...';

        const options = profiles.map(p => ({ label: p.title.rendered, value: p.id }));

        return (
            <div style={{ padding: '20px', border: '1px solid #39e09b', borderRadius: '8px' }}>
                <h4>SaaS Lead Capture Form</h4>
                <div style={{ marginBottom: '15px' }}>
                    <label style={{ display: 'block', marginBottom: '5px' }}>Form Title</label>
                    <input
                        type="text"
                        value={attributes.title}
                        onChange={(e) => setAttributes({ title: e.target.value })}
                        style={{ width: '100%', padding: '8px', border: '1px solid #ccc', borderRadius: '4px' }}
                    />
                </div>
                <SelectControl
                    label="Link leads to Profile"
                    value={attributes.profile_id}
                    options={[{ label: 'Select a profile...', value: 0 }, ...options]}
                    onChange={(val) => setAttributes({ profile_id: parseInt(val) })}
                />
            </div>
        );
    }),
    save: () => null
});
