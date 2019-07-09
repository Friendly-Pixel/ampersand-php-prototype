import { TestBed } from '@angular/core/testing';

import { NotificationCenterService } from './notification-center.service';

describe('NotificationCenterService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: NotificationCenterService = TestBed.get(NotificationCenterService);
    expect(service).toBeTruthy();
  });
});
