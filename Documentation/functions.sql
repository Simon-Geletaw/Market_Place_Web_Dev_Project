-- Functions for workflow transitions and logging
CREATE OR REPLACE FUNCTION marketplace.log_status_change(
    p_request_id UUID,
    p_old_status request_status,
    p_new_status request_status,
    p_changed_by UUID
)
RETURNS void AS $$
DECLARE
    v_duration INT;
BEGIN
    SELECT EXTRACT(EPOCH FROM (NOW() - updated_at))::INT
    INTO v_duration
    FROM marketplace.SERVICE_REQUESTS
    WHERE request_id = p_request_id;

    INSERT INTO marketplace.STATUS_HISTORY (request_id, old_status, new_status, duration_seconds, changed_by)
    VALUES (p_request_id, p_old_status, p_new_status, v_duration, p_changed_by);
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION marketplace.accept_offer(
    p_offer_id UUID,
    p_customer_id UUID
)
RETURNS void AS $$
DECLARE
    v_request_id UUID;
    v_old_status request_status;
BEGIN
    SELECT request_id INTO v_request_id
    FROM marketplace.OFFERS WHERE offer_id = p_offer_id;

    SELECT status INTO v_old_status
    FROM marketplace.SERVICE_REQUESTS WHERE request_id = v_request_id;

    UPDATE marketplace.OFFERS SET status = 'Accepted' WHERE offer_id = p_offer_id;

    UPDATE marketplace.SERVICE_REQUESTS
    SET status = 'Assigned', accepted_offer_id = p_offer_id
    WHERE request_id = v_request_id;

    PERFORM marketplace.log_status_change(v_request_id, v_old_status, 'Assigned', p_customer_id);

    INSERT INTO marketplace.AUDIT_LOGS (user_id, action, entity_type, entity_id, details)
    VALUES (p_customer_id, 'offer_accepted', 'offer', p_offer_id,
            'Request #' || v_request_id || ' moved to Assigned');
END;
$$ LANGUAGE plpgsql;

-- Maintain updated_at automatically
CREATE OR REPLACE FUNCTION marketplace.set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;