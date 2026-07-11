export function canAccessAny(userPermissions: string[] | undefined, requiredPermissions: string[] | undefined) {
  if (!requiredPermissions || requiredPermissions.length === 0) {
    return true
  }

  return requiredPermissions.some((permission) => userPermissions?.includes(permission))
}
